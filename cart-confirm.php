<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>カート - SATONOMI</title>
</head>
<body>

<?php require 'header.php'; ?>

  <!-- 商品リスト -->
  <main id="cart">
    <div>
      <img src="https://example.com/sample.jpg" alt="長州地サイダー" width="80">
      <div>
        <p>長州地サイダー</p>
        <p id="price">￥10,580</p>
      </div>
      <div>
        <button id="minus">－</button>
        <span id="count">2</span>
        <button id="plus">＋</button>
      </div>
    </div>
  </main>

  <!-- 合計金額とボタン -->
  <footer>
    <p>合計 ￥<span id="total">10,580</span></p>
    <button id="confirm">購入確認へ進む</button>
  </footer>

  <script>
    const pricePerItem = 5290; // 1本あたりの価格
    let count = 2;

    const countEl = document.getElementById("count");
    const totalEl = document.getElementById("total");

    document.getElementById("plus").addEventListener("click", () => {
      count++;
      updateTotal();
    });

    document.getElementById("minus").addEventListener("click", () => {
      if (count > 1) count--;
      updateTotal();
    });

    function updateTotal() {
      countEl.textContent = count;
      totalEl.textContent = (pricePerItem * count).toLocaleString();
    }

    document.getElementById("confirm").addEventListener("click", () => {
      alert("購入確認画面へ進みます。");
    });
  </script>

</body>
</html>
