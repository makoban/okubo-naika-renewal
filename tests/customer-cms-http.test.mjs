import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import net from 'node:net';
import {spawn, spawnSync, execFileSync} from 'node:child_process';
import {setTimeout} from 'node:timers/promises';

const php = spawnSync('which', ['php'], {encoding:'utf8'}).stdout?.trim();
test('customer login and publication reject unauthenticated, cross-origin and stale writes', {skip: !php}, async () => {
  const root = path.resolve(import.meta.dirname, '..');
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'okubo-customer-http-'));
  const baseline = JSON.parse(fs.readFileSync(path.join(root,'published-content.json')));
  const hash = execFileSync(php,['-r',"echo password_hash('synthetic-test-password', PASSWORD_BCRYPT, ['cost'=>4]);"], {encoding:'utf8'});
  fs.writeFileSync(path.join(directory,'config.json'),JSON.stringify({username:'test-clinic',passwordHash:hash}),{mode:0o600});
  const port = await new Promise(resolve => { const server=net.createServer();server.listen(0,'127.0.0.1',()=>{const p=server.address().port;server.close(()=>resolve(p));}); });
  const origin=`http://127.0.0.1:${port}`;
  const server=spawn(php,['-S',`127.0.0.1:${port}`,'-t',root],{env:{OKUBO_CMS_DATA_DIR:directory},stdio:'ignore'});
  let cookie='';
  const request=async(url,options={})=>{const r=await fetch(origin+url,{redirect:'manual',...options,headers:{...(cookie?{Cookie:cookie}:{}),...options.headers}});if(r.headers.get('set-cookie'))cookie=r.headers.get('set-cookie').split(';')[0];return r;};
  try {
    for(let i=0;i<40;i++){try{await fetch(origin+'/cms/login.php');break;}catch{await setTimeout(50);}}
    assert.equal((await request('/cms/')).status,302);
    assert.equal((await request('/cms/api.php',{method:'POST',body:'{}'})).status,401);
    let response=await request('/cms/login.php');
    const loginHtml=await response.text();
    const csrf=loginHtml.match(/name="csrf" value="([a-f0-9]+)"/)[1];
    assert.match(response.headers.get('set-cookie') || '',/HttpOnly/i);
    assert.match(response.headers.get('set-cookie') || '',/SameSite=Strict/i);
    response=await request('/cms/login.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded',Origin:origin},body:new URLSearchParams({csrf,username:'test-clinic',password:'synthetic-test-password'})});
    assert.equal(response.status,303);
    response=await request('/cms/');assert.equal(response.status,200);
    const html=await response.text();assert.ok(!html.includes('github.com'));
    const token=html.match(/window.OKUBO_CMS_CSRF = "([a-f0-9]+)"/)[1];
    const content=structuredClone(baseline.content);content.topics[0].body+='\nSynthetic customer publication';
    const payload={baseRevision:baseline.revision,content};
    const publish=(data,extra={})=>request('/cms/api.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':token,Origin:origin,...extra},body:JSON.stringify(data)});
    assert.equal((await publish(payload,{'X-CSRF-Token':'wrong'})).status,403);
    assert.equal((await publish(payload,{Origin:'https://example.invalid'})).status,403);
    response=await publish(payload);assert.equal(response.status,200);const result=await response.json();
    const actual=await (await request('/published-content.php?format=json')).json();
    assert.equal(actual.revision,result.revision);assert.deepEqual(actual.content,content);
    assert.equal((await publish(payload)).status,409);
    payload.baseRevision=result.revision;payload.content.topics[0].link='javascript:alert(1)';
    assert.equal((await publish(payload)).status,422);
    assert.equal((await request('/cms/logout.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded',Origin:origin},body:new URLSearchParams({csrf:token})})).status,303);
    assert.equal((await publish(payload)).status,401);
  } finally {server.kill();fs.rmSync(directory,{recursive:true,force:true});}
});
