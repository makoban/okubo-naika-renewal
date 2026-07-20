(function () {
  document.body.classList.add("review-site");

  if (!document.querySelector(".review-banner")) {
    const banner = document.createElement("div");
    banner.className = "review-banner";
    banner.innerHTML =
      '<span>構成確認用プレビュー（本番サイトではありません）</span><a href="./admin-preview.html">編集画面イメージを見る</a>';
    document.body.prepend(banner);
  }

  const thumbs = Array.from(document.querySelectorAll("[data-gallery-thumb]"));
  if (thumbs.length) {
    const dialog = document.createElement("dialog");
    dialog.className = "gallery-dialog";
    dialog.setAttribute("aria-label", "院内写真の拡大表示");
    dialog.innerHTML =
      '<button type="button" aria-label="閉じる">×</button><figure><img src="../assets/client-202607/clinic-exterior.jpg" alt="おおくぼ内科クリニック外観" /><figcaption></figcaption></figure>';
    document.body.append(dialog);

    const dialogImage = dialog.querySelector("img");
    const dialogCaption = dialog.querySelector("figcaption");

    thumbs.forEach((thumb) => {
      thumb.addEventListener("click", () => {
        const image = thumb.querySelector("img");
        dialogImage.src = image.src;
        dialogImage.alt = image.alt;
        dialogCaption.textContent = thumb.dataset.caption || image.alt;
        dialog.showModal();
      });
    });

    dialog.querySelector("button").addEventListener("click", () => dialog.close());
    dialog.addEventListener("click", (event) => {
      if (event.target === dialog) dialog.close();
    });
  }
})();
