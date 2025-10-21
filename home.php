<?php

session_start();
include 'my connexion.php';

// Récupérer les catégories
$categories = $conn->query("SELECT code_categorie, nom_categorie FROM Categories ORDER BY nom_categorie ASC");

// Gestion des filtres
$where = [];
$params = [];
$types = "";

if (isset($_GET['promo'])) {
    $where[] = "en_promotion = 1";
}
if (isset($_GET['categorie']) && $_GET['categorie'] !== "") {
    $where[] = "code_categorie = ?";
    $params[] = $_GET['categorie'];
    $types .= "s";
}

$orderBy = "designation ASC";
if (isset($_GET['order'])) {
    switch ($_GET['order']) {
        case 'designation_asc':
            $orderBy = "designation ASC";
            break;
        case 'designation_desc':
            $orderBy = "designation DESC";
            break;

    }
}
$sql = "SELECT * FROM Produits";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY $orderBy";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$produits = $stmt->get_result();

$successMsg = "";
if (isset($_GET['demande']) && $_GET['demande'] == 1) {
    $successMsg = "Votre demande a été effectuée avec succès !";
}
// Add this for payment success
if (isset($_GET['paiement']) && $_GET['paiement'] == 'success') {
    $successMsg = "Paiement effectué avec succès !";
}

// Avant la boucle produits
$idsWishlist = [];
if (isset($_SESSION['client_id'])) {
    $res = $conn->query("SELECT id_produit FROM wishes WHERE id_client = " . intval($_SESSION['client_id']));
    while($row = $res->fetch_assoc()) $idsWishlist[] = $row['id_produit'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>BOUTIQUE MB - Accueil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Google Fonts & Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <style>

       body {
    background: linear-gradient(120deg, #f0f4ff 0%, #e8f5e9 100%);
    font-family: 'Poppins', Arial, sans-serif;
    margin: 0;
    padding: 0;
    color: #232526;
    min-height: 100vh;
}
.header-glass {
    position: sticky;
    top: 0;
    z-index: 10;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(12px);
    box-shadow: 0 2px 16px rgba(31,41,55,0.07);
    padding: 28px 32px 18px 32px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 32px;
    width: 100vw;
    margin-left: calc(-50vw + 50%);
    margin-right: calc(-50vw + 50%);
    left: 0;
    right: 0;
}
.logo-title {
    font-size: 2.2rem;
    font-weight: 800;
    letter-spacing: 2px;
    color: #1f2937;
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0 32px;
    position: relative;
    justify-content: flex-end; /* Add this to push button to right */
}

.logo-title::before {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    pointer-events: none;
}

#darkModeToggle {
    margin-left: 0; /* Remove the auto margin */
    z-index: 1; /* Ensure button stays clickable */
}

.search-bar-main {
    margin: 18px 0 0 0;
    width: 100%;
    max-width: 420px;
    border-radius: 14px;
    border: none;
    padding: 12px 18px;
    font-size: 1.1rem;
    background: rgba(255,255,255,0.7);
    box-shadow: 0 2px 12px rgba(31,41,55,0.06);
    outline: none;
    transition: box-shadow 0.18s;
}
.search-bar-main:focus {
    box-shadow: 0 4px 18px #d7266022;
}
.filters-bar {
    display: flex;
    gap: 18px;
    justify-content: center;
    align-items: center;
    margin: 0 0 36px 0;
    flex-wrap: wrap;
}
.filters-bar .btn, .filters-bar .form-select {
    border-radius: 999px;
    font-weight: 600;
    font-size: 1rem;
    border: none;
    background: rgba(255,255,255,0.7);
    box-shadow: 0 2px 8px rgba(31,41,55,0.06);
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}
.filters-bar .btn:hover, .filters-bar .form-select:focus {
    background: #e8f5e9;
    color: #1f2937;
}
.filters-bar .btn-promo {
    color: #fff;
    background: linear-gradient(90deg, #d72660 0%, #fbb13c 100%);
}
.filters-bar .btn-promo:hover {
    background: linear-gradient(90deg, #fbb13c 0%, #d72660 100%);
}
.filters-bar .btn-wishlist {
    color: #d72660;
    background: #fff;
    border: 1.5px solid #d72660;
}
.filters-bar .btn-wishlist:hover {
    background: #d72660;
    color: #fff;
}
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 32px;
    width: 100%;
    padding: 0;
}
.product-card {
    position: relative;
    padding: 0;
    overflow: hidden;
    border-radius: 22px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    background: rgba(255,255,255,0.85);
    box-shadow: 0 8px 32px rgba(124, 58, 237, 0.10), 0 1.5px 8px #a17fff22;
    transition: box-shadow 0.22s, transform 0.22s, border 0.18s, background 0.22s;
    min-height: 340px;
}
.product-card:hover {
    box-shadow: 0 16px 48px #7c3aed33, 0 4px 24px #a17fff33;
    background: linear-gradient(135deg, #f8f7ff 60%, #e0d7ff 100%);
}
.product-img {
    width: 100%;
    aspect-ratio: 1/1;
    height: 220px;
    object-fit: cover;
    border-radius: 22px 22px 0 0;
    margin: 0;
    background: #f6f3ff;
    box-shadow: none;
    border: none;
    transition: transform 0.22s, box-shadow 0.22s;
    display: block;
}
.product-card:hover .product-img {
    transform: scale(1.04) ;
}
.product-card .card-body {
    padding: 18px 18px 14px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1 1 auto;
    background: transparent;
}
@media (max-width: 900px) {
    .product-card { min-height: 260px; }
    .product-img { height: 150px; }
}
@media (max-width: 600px) {
    .product-card { min-height: 180px; }
    .product-img { height: 110px; }
}
.star-rating {
    color: #fbb13c;
    font-size: 1.1rem;
    margin: 6px 0 2px 0;
    letter-spacing: 2px;
}
.in-stock-badge {
    background: #fbb13c;
    color: #fff;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 13px;
    margin-left: 8px;
    font-weight: 600;
}
.out-stock {
    background: #d72660;
    color: #fff;
    padding: 6px 14px;
    border-radius: 8px;
    margin-top: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.footer {
    text-align: center;
    margin-top: 42px;
    color: #666;
    font-size: 14px;
}
@media (max-width: 900px) {
    .container { padding: 22px 10px; }
    .product-grid { gap: 18px; }
}
@media (max-width: 600px) {
    .header-glass { padding: 18px 0 10px 0; }
    .logo-title { font-size: 1.3rem; }
    .product-grid { grid-template-columns: 1fr; }
    .product-card { padding: 12px 6px; }
    .product-img { max-width: 90px; height: 70px; }
}

/* Update the existing container styles */
.container {
    width: 100%;
    max-width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow-x: hidden;
}

/* Add responsive padding adjustments */
@media (max-width: 768px) {
    .container {
        padding: 0 16px !important;
    }
}

/* Update product grid for better full-width display */
.row {
    margin-right: 0 !important;
    margin-left: 0 !important;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 32px;
    width: 100%;
    padding: 0;
}

/* Update header glass for full width */
.header-glass {
    width: 100%;
    padding-left: 32px;
    padding-right: 32px;
    margin-left: -32px;
    margin-right: -32px;
}

@media (max-width: 768px) {
    .header-glass {
        padding-left: 16px;
        padding-right: 16px;
        margin-left: -16px;
        margin-right: -16px;
    }
}

input[type="number"] {
    width: 1000px;
    position: relative;
    top:8.9px;
}
.filters {
    margin-bottom: 32px;
}

body.dark-mode {
    background: linear-gradient(120deg, #1a1c2e 0%, #2d3047 100%) !important;
    color: #e1e7ff !important;
}
body.dark-mode .header-glass {
    background: rgba(45, 48, 71, 0.85) !important;
    color: #e1e7ff !important;
    box-shadow: 0 2px 16px rgba(161, 127, 255, 0.15);
}
body.dark-mode .logo-title,
body.dark-mode .footer {
    color: #e1e7ff !important;
}
body.dark-mode .product-card {
    background: rgba(45, 48, 71, 0.75) !important;
    color: #e1e7ff !important;
    border: 1.5px solid #3f4261 !important;
    box-shadow: 0 8px 32px rgba(161, 127, 255, 0.1);
}
body.dark-mode .product-card:hover {
    box-shadow: 0 16px 48px rgba(161, 127, 255, 0.2);
    border: 1.5px solid #a17fff !important;
}
body.dark-mode .filters-bar .btn,
body.dark-mode .filters-bar .form-select {
    background: #2d3047 !important;
    color: #e1e7ff !important;
    border: 1px solid #3f4261 !important;
}
body.dark-mode .filters-bar .btn-promo {
    background: linear-gradient(90deg, #a17fff 0%, #ff7eb3 100%) !important;
    color: #fff !important;
    border: none !important;
}
body.dark-mode .wishlist-heart-btn {
    background: #2d3047 !important;
    color: #ff7eb3 !important;
    border-color: #ff7eb3 !important;
}
body.dark-mode .wishlist-heart-btn.added {
    background: #ff7eb3 !important;
    color: #2d3047 !important;
    border-color: #ff7eb3 !important;
}
body.dark-mode .btn-details, 
body.dark-mode .btn-order {
    background: #3f4261 !important;
    color: #a17fff !important;
    border: 1px solid #a17fff !important;
}
body.dark-mode .btn-details:hover, 
body.dark-mode .btn-order:hover {
    color: #2d3047 !important;
}
body.dark-mode .product-img {
    background: #3f4261 !important;
    box-shadow: 0 2px 8px rgba(161, 127, 255, 0.2);
}
body.dark-mode .modal-content {
    background: #2d3047 !important;
    color: #e1e7ff !important;
    border: 1px solid #3f4261;
}
body.dark-mode .promo-badge {
    background: linear-gradient(90deg, #a17fff, #ff7eb3) !important;
    box-shadow: 0 2px 8px rgba(161, 127, 255, 0.3);
}
body.dark-mode .product-price,
body.dark-mode .prix {
    color: #4ade80 !important; /* Light green color for better visibility */
}

body.dark-mode span[style*="color:#ff4d4f"],
body.dark-mode span[style*="color:#232526"] {
    color: #4ade80 !important; /* Light green for regular prices */
}

body.dark-mode span[style*="color:#888"] {
    color: #9ca3af !important; /* Lighter gray for strikethrough prices */
}

body.dark-mode .product-price {
    text-shadow: 0 0 10px rgba(74, 222, 128, 0.3);
}
body.dark-mode .footer {
    color: #8f9ac5 !important;
}
body.dark-mode .search-bar-main {
    background: rgba(45, 48, 71, 0.9) !important;
    color: #e1e7ff !important;
    border: 1px solid #3f4261;
}
body.dark-mode .in-stock-badge {
    background: #a17fff !important;
}
body.dark-mode .out-stock {
    background: #ff7eb3 !important;
}
body.dark-mode .btn-purchases {
    background: #a17fff !important;
    color: #e1e7ff !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(161, 127, 255, 0.2);
}

body.dark-mode .btn-purchases:hover {
    background: linear-gradient(90deg, #a17fff 0%, #ff7eb3 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 127, 255, 0.3);
}

/* Add these new styles for the navigation */
.main-nav {
    width: 100%;
    margin: 15px 0;
    padding: 0 20px;
}

.nav-links {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.nav-link {
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    color: #232526;
    font-weight: 600;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.7);
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-link:hover {
    background: #e8f5e9;
    transform: translateY(-2px);
}

.nav-link.active {
    background: #4f46e5;
    color: white;
}

.nav-link-logout {
    background: #dc3545;
    color: white;
}

.nav-link-logout:hover {
    background: #bb2d3b;
    color: white;
}

/* Dark mode styles for navigation */
body.dark-mode .nav-link {
    background: rgba(45, 48, 71, 0.75);
    color: #e1e7ff;
}

body.dark-mode .nav-link:hover {
    background: #3f4261;
}

body.dark-mode .nav-link.active {
    background: #a17fff;
    color: #2d3047;
}

body.dark-mode .nav-link-logout {
    background: #ff7eb3;
    color: #2d3047;
}

body.dark-mode .nav-link-logout:hover {
    background: #ff5c9e;
}

/* Footer base styles */
.mb-footer {
  background: linear-gradient(120deg,#f0f4ff 0%,#e8f5e9 100%);
  color: #232526;
  padding: 40px 0 18px 0;
  margin-top: 48px;
  font-size: 1rem;
  transition: background 0.3s, color 0.3s;
}
.mb-footer-container {
  max-width: 1200px;
  margin: auto;
  padding: 0 16px;
}
.mb-footer-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 32px;
}
.mb-footer-col {
  flex: 1 1 220px;
  min-width: 180px;
  margin-bottom: 18px;
}
.mb-footer-col h4, .mb-footer-col h5 {
  font-weight: 700;
  margin-bottom: 12px;
}
.mb-footer-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
  line-height: 2;
}
.mb-footer-col ul li a {
  color: inherit;
  text-decoration: none;
  transition: color 0.2s;
}
.mb-footer-col ul li a:hover {
  color: #d72660;
  text-decoration: underline;
}
.mb-footer-social a {
  color: inherit;
  font-size: 1.3em;
  margin-right: 12px;
  transition: color 0.2s;
}
.mb-footer-social a:hover {
  color: #d72660;
}
.mb-footer-hr {
  border-color: #bbb;
  margin: 28px 0 12px 0;
}
.mb-footer-copy {
  text-align: center;
  color: #666;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 900px) {
  .mb-footer-row { flex-direction: column; gap: 0; }
  .mb-footer-col { min-width: 0; }
}

/* Dark mode styles */
body.dark-mode .mb-footer {
  background: linear-gradient(120deg,#2d3047 0%,#232526 100%);
  color: #e1e7ff;
}
body.dark-mode .mb-footer-col ul li a,
body.dark-mode .mb-footer-social a {
  color: #e1e7ff;
}
body.dark-mode .mb-footer-col ul li a:hover,
body.dark_mode .mb-footer-social a:hover {
  color: #fbb13c;
}
body.dark-mode .mb-footer-hr {
  border-color: #444;
}
body.dark-mode .mb-footer-copy {
  color: #8f9ac5;
}

.custom-header-section {
  width: 100vw;
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  margin-bottom: 32px;
  position: relative;
  overflow: hidden;
}
.header-image-wrapper {
  position: relative;
  width: 100%;
  height: 340px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.header-image {
  width: 100%;
  height: 340px;
  object-fit: cover;
  display: block;
  filter: brightness(0.9);
}
.header-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 1;
}
.header-content {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 2;
  transform: translate(-50%, -50%);
  text-align: center;
  color: #fff;
  width: 90%;
}
.header-title {
  font-size: 2.8rem;
  font-weight: 800;
  margin-bottom: 18px;
  text-shadow: 0 4px 24px #0008;
  letter-spacing: 2px;
}
.header-btn {
  background: linear-gradient(90deg,rgb(27, 73, 226) 0%,rgb(161, 21, 197) 100%);
  color: #fff;
  border: none;
  border-radius: 9px; 
  padding: 5px 15px;
  font-size: 1.2rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 2px 12px #d7266022;
  transition: background 0.2s, transform 0.15s;
}
.header-btn:hover {
  transform: scale(1.06);
}
body.dark-mode .header-description {
  
  color: #e1e7ff;
  
}
@media (max-width: 700px) {
  .header-image-wrapper, .header-image {
    height: 180px;
  }
  .header-title {
    font-size: 1.5rem;
  }
  .header-btn {
    font-size: 1rem;
    padding: 10px 22px;
  }
  
}

.categories-nav {
  background: #7c3aed;
  padding: 0 0 0 24px;
  margin-bottom: 32px;
  box-shadow: 0 2px 8px #7c3aed22;
  border-radius: 5px;

}
.categories-nav ul {
  display: flex;
  align-items: center;
  gap: 18px;
  list-style: none;
  margin: 0;
  padding: 0;
  height: 54px;
}
.categories-nav li {
  display: flex;
  align-items: center;
}
.categories-nav a {
  color: #fff;
  font-weight: 600;
  font-size: 1.08rem;
  text-decoration: none;
  padding: 8px 18px;
  border-radius: 22px;
  transition: background 0.18s, color 0.18s;
  display: flex;
  align-items: center;
  gap: 7px;
}
.categories-nav a.active,
.categories-nav a:hover {
  background: #fff;
  color: #7c3aed;
  
}
body.dark-mode .categories-nav {
  background: #2d3047;
  box-shadow: 0 2px 8px #a17fff22;
}
body.dark-mode .categories-nav a {
  color: #e1e7ff;
}
body.dark-mode .categories-nav a.active,
body.dark-mode .categories-nav a:hover {
  background: #a17fff;
  color: #2d3047;
}
#panierBtn {
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 9999;
    background: #7c3aed;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 62px;
    height: 62px;
    box-shadow: 0 4px 18px #7c3aed44;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.1rem;
    cursor: pointer;
    transition: background 0.2s, transform 0.18s;
}
#panierBtn:hover {
    background: #a17fff;
    transform: scale(1.08);
}
.categories-nav .btn-outline-light {
    background: transparent;
    color: #fff;
    border: 1.5px solid #fff;
    margin-left: 0;
    margin-right: 0;
    font-size: 0.98rem;
    transition: background 0.18s, color 0.18s;
}
.categories-nav .btn-outline-light.active,
.categories-nav .btn-outline-light:focus,
.categories-nav .btn-outline-light:hover {
    background: #fff;
    color: #7c3aed;
    border-color: #fff;
}
@media (max-width: 700px) {
    #panierBtn {
        right: 12px;
        bottom: 12px;
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
    }
    .categories-nav ul {
        flex-wrap: wrap;
        gap: 8px;
    }
    .categories-nav .btn-outline-light {
        font-size: 0.85rem;
        padding: 3px 8px;
    }
}

/* --- Floating Heart Button with Glass Effect --- */
.wishlist-heart-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 5;
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.wishlist-heart-btn .wishlist-heart {
    font-size: 1.5em;
    color: #ff4d4f;
    opacity: 0.7;
    transition: all 0.3s ease;
    transform-origin: center;
    text-shadow: 0 2px 5px rgba(255, 77, 79, 0.3);
}

.wishlist-heart-btn:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: translateY(-2px);
}

.wishlist-heart-btn:hover .wishlist-heart {
    opacity: 1;
    transform: scale(1.15);
}

.wishlist-heart-btn.added {
    background: rgba(255, 77, 79, 0.25);
}

.wishlist-heart-btn.added .wishlist-heart {
    opacity: 1;
    color: #ff4d4f;
    animation: heartBeat 0.35s ease-in-out;
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    25% { transform: scale(1.3); }
    50% { transform: scale(0.95); }
    75% { transform: scale(1.2); }
    100% { transform: scale(1.1); }
}

/* --- Creative Promo Badge --- */
.promo-badge {
    position: absolute;
    top: 15px;
    left: 0;
    background: linear-gradient(45deg, #ff4d4f, #ff7eb3);
    color: white;
    padding: 8px 15px 8px 25px;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    border-radius: 0 25px 25px 0;
    box-shadow: 0 4px 15px rgba(255, 77, 79, 0.25);
    transform: translateX(-5px);
    transition: all 0.3s ease;
}

.promo-badge::before {
    content: '🔥';
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.9em;
}

.promo-badge::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -5px;
    width: 5px;
    height: 5px;
    background: #ff4d4f;
    border-radius: 0 0 0 5px;
}

.product-card:hover .promo-badge {
    transform: translateX(0);
    background: linear-gradient(45deg, #ff7eb3, #ff4d4f);
}
.promo-dates {
        transition: all 0.3s ease;
        border: 1px dashed #ff4d4f;
    }

    .product-card:hover .promo-dates {
        background: rgba(255, 77, 79, 0.15);
        transform: translateY(-2px);
    }

    body.dark-mode .promo-dates {
        background: rgba(161, 127, 255, 0.1);
        border-color: #a17fff;
        color: #a17fff;
    }

    body.dark-mode .product-card:hover .promo-dates {
        background: rgba(161, 127, 255, 0.15);
    }

/* Dark mode adjustments */
body.dark-mode .wishlist-heart-btn {
    background: rgba(0, 0, 0, 0.25);
}

body.dark-mode .promo-badge {
    background: linear-gradient(45deg, #a17fff, #ff7eb3);
}

body.dark-mode .promo-badge::after {
    background: #a17fff;
}
    </style>
</head>
<body>
<div class="custom-header-section">
  <div class="header-image-wrapper">
    <img src="uploads/WhatsApp Image 2025-05-31 at 13.42.57_229ffec4.jpg" class="header-image" alt="Header Image">
    <div class="header-overlay"></div>
    <div class="header-content">
      <h1 class="header-title">WELCOME TO BOUTIQUE MB</h1>
      <div class="header-description">
        <p>Découvrez notre sélection colletion exuclusive</p>
  </div>
      <button class="header-btn" onclick="scrollToProducts()">Voir nos produits</button>
    </div>
  </div>
  
</div>
<div class="container">
    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= $successMsg ?></div>
    <?php endif; ?>
              <!-- Add this navigation bar for categories above your products grid -->
<nav class="categories-nav">
  <ul style="margin-bottom:0;">
    <li>
      <a href="home.php" class="<?= !isset($_GET['categorie']) ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i> Tous
      </a>
    </li>
    <?php foreach ($categories as $cat): ?>
      <li>
        <a href="home.php?categorie=<?= htmlspecialchars($cat['code_categorie']) ?>"
           class="<?= (isset($_GET['categorie']) && $_GET['categorie'] == $cat['code_categorie']) ? 'active' : '' ?>">
          <?= htmlspecialchars($cat['nom_categorie']) ?>
        </a>
      </li>
    <?php endforeach; ?>
    <!-- Ajoute les boutons de tri ici -->
    <li>
      <form method="get" style="display:flex;gap:6px;align-items:center;margin-left:18px;">
        <?php if (isset($_GET['categorie'])): ?>
          <input type="hidden" name="categorie" value="<?= htmlspecialchars($_GET['categorie']) ?>">
        <?php endif; ?>
        
<button type="button" name="order" value="designation_asc" class="btn btn-outline-light btn-sm <?= (isset($_GET['order']) && $_GET['order']=='designation_asc') ? 'active' : '' ?>" style="border-radius:18px;padding:4px 12px;font-weight:600;">
  Nom A-Z
</button>
<button type="button" name="order" value="designation_desc" class="btn btn-outline-light btn-sm <?= (isset($_GET['order']) && $_GET['order']=='designation_desc') ? 'active' : '' ?>" style="border-radius:18px;padding:4px 12px;font-weight:600;">
  Nom Z-A
</button>
      </form>
    </li>
  </ul>
</nav>

    <div class="header-glass">
        <div class="logo-title">
            <div style="position: absolute; left: 50%; transform: translateX(-50%); display: flex; align-items: center;">
                <i class="fas fa-shopping-bag" style="margin-right: 10px;"></i> BOUTIQUE MB
            </div>
            <button id="darkModeToggle" title="Mode sombre" style="background:none;border:none;cursor:pointer;font-size:1.2em;">
                <i class="fas fa-moon"></i>
            </button>
        </div>
 
        <!-- New Navigation Bar -->
        <nav class="main-nav">
            <div class="nav-links">
                <a href="?promo=1" class="nav-link <?= isset($_GET['promo']) ? 'active' : '' ?>">
                    <i class="fas fa-fire"></i> En promo
                </a>
                <?php if (isset($_SESSION['client_id'])): ?>
                    <a href="javascript:viewWishlist()" class="nav-link">
                        <i class="fas fa-heart"></i> Ma wishlist
                    </a>
                    <a href="logout.php" class="nav-link nav-link-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php endif; ?>
            </div>
        </nav>
        
        <form method="get" style="width:100%;display:flex;justify-content:center;">
            <input type="text" name="search" class="search-bar-main" placeholder="Rechercher un produit..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        </form>
    </div>

    <div class="row row-cols-1 row-cols-md-5 g-3" id="productsGrid">
        <?php
        // Recherche par mot-clé
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        while($prod = $produits->fetch_assoc()):
            if ($search && stripos($prod['designation'], $search) === false) continue;
            
            $isInWishlist = in_array($prod['id_produit'], $idsWishlist);
        ?>
        <div class="col">
            <div class="product-card h-100 d-flex flex-column align-items-stretch" data-aos="zoom-in-up">
                <?php if ($prod['photo']): ?>
                    <img src="uploads/<?= htmlspecialchars($prod['photo']) ?>" class="product-img" alt="">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x300?text=Sans+Image" class="product-img" alt="">
                <?php endif; ?>
                
                <!-- Add promotion dates below image -->
                <?php if ($prod['en_promotion'] && !empty($prod['date_debut_promo']) && !empty($prod['date_fin_promo'])): ?>
                    <div class="promo-dates text-center mt-2" style="
                        padding: 8px;
                        background: rgba(255, 77, 79, 0.1);
                        border-radius: 8px;
                        margin: 8px 12px 0 12px;
                        font-size: 0.95rem;
                        color: #ff4d4f;
                        border: 1px dashed #ff4d4f;
                    ">
                        <i class="far fa-calendar-alt"></i>
                        Du <?= date('d/m/Y', strtotime($prod['date_debut_promo'])) ?>
                        au <?= date('d/m/Y', strtotime($prod['date_fin_promo'])) ?>
                    </div>
                <?php endif; ?>

                <div class="card-body d-flex flex-column align-items-center" style="flex:1;">
                    <div style="width:100%;text-align:right;">
                        <button type="button"
                                class="wishlist-heart-btn<?= $isInWishlist ? ' added' : '' ?>"
                                data-id="<?= $prod['id_produit'] ?>"
                                onclick="toggleWishlist(this)">
                            <span class="wishlist-heart" title="Ajouter à la wishlist">&#10084;</span>
                            <span class="wishlist-text"><?= $isInWishlist ? '' : '' ?></span>
                        </button>
                    </div>
                    <h5 class="product-title">
                        <?= htmlspecialchars($prod['designation']) ?>
                        <?php if ($prod['en_promotion']): ?>
                            <?php if ($prod['en_promotion'] && $prod['prix_promotion'] && $prod['prix'] > 0): ?>
    <?php
        $percent = round(100 - ($prod['prix_promotion'] / $prod['prix']) * 100);
        $dateDebut = !empty($prod['date_debut_promo']) ? date('d/m/Y', strtotime($prod['date_debut_promo'])) : '';
        $dateFin = !empty($prod['date_fin_promo']) ? date('d/m/Y', strtotime($prod['date_fin_promo'])) : '';
    ?>
    <span class="promo-badge">
        Promo -<?= $percent ?>%
        <?php if ($dateDebut && $dateFin): ?>
            <br>
            <small style="font-weight:400;">
                du <?= $dateDebut ?> au <?= $dateFin ?>
            </small>
        <?php endif; ?>
    </span>
<?php endif; ?>
                        <?php endif; ?>
                    </h5>
                    <div>
                        <?php if ($prod['en_promotion'] && $prod['prix_promotion']): ?>
                            <span style="color:#ff4d4f;font-weight:bold;"><?= number_format($prod['prix_promotion'],2) ?> DH</span>
                            <span style="text-decoration:line-through;color:#888;margin-left:8px;"><?= number_format($prod['prix'],2) ?> DH</span>
                        <?php else: ?>
                            <span style="color:#232526;font-weight:bold;"><?= number_format($prod['prix'],2) ?> DH</span>
                        <?php endif; ?>
                    </div>
                    <a href="produit.php?id=<?= $prod['id_produit'] ?>" class="btn btn-outline-primary">Voir les détails</a>
                    <?php if ($prod['quantite_stock'] > 0): ?>
    <?php if (isset($_SESSION['client_id'])): ?>
        <!-- Redirect to paiement.php with product ID -->
        <button type="button"
            class="btn btn-success btn-order mt-2"
            onclick="window.location.href='paiement.php?id_produit=<?= $prod['id_produit'] ?>'">
            Commander
        </button>
    <?php else: ?>
        <a href="login.php?redirect=home.php&id_produit=<?= $prod['id_produit'] ?>&quantite=1" class="btn btn-outline-primary btn-order mt-1">Se connecter</a>
    <?php endif; ?>
<?php else: ?>
    <div class="out-stock">Rupture de stock</div>
<?php endif; ?>
                    <div class="star-rating">
                        <?= "&#9733;&#9733;&#9733;&#9733;&#9734;"; ?>
                    </div>
                    <?php if ($prod['quantite_stock'] > 0): ?>
                        <span class="in-stock-badge">En stock</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <hr class="my-4">
    
    <!-- Modern Footer Start -->
<footer class="site-footer mb-footer">
  <div class="mb-footer-container">
    <div class="mb-footer-row">
      <!-- Brand & Social -->
      <div class="mb-footer-col">
        <h4>VeronaShop</h4>
        <p>Apportant l'élégance et la passion de l'artisanat italien à votre porte depuis 2025.</p>
        <div class="mb-footer-social">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-pinterest"></i></a>
        </div>
      </div>
      <!-- Quick Links -->
      <div class="mb-footer-col">
        <h5>Liens rapides</h5>
        <ul>
          <li><a href="home.php">Accueil</a></li>
          <li><a href="home.php">Produits</a></li>
          <li><a href="home.php">Catégories</a></li>
          <li><a href="?promo=1">Promotions</a></li>
        </ul>
      </div>
      <!-- Service Client -->
      <div class="mb-footer-col">
        <h5>Service client</h5>
        <ul>
          <li><a href="#">Contactez-nous</a></li>
          <li><a href="#">Livraison</a></li>
          <li><a href="#">Retours & Échanges</a></li>
          <li><a href="#">FAQ</a></li>
          <li><a href="#">Politique de confidentialité</a></li>
        </ul>
      </div>
      <!-- Contact -->
      <div class="mb-footer-col">
        <h5>Contact</h5>
        <ul>
          <li><i class="fas fa-map-marker-alt"></i> centre ville, Verona, kenitra</li>
          <li><i class="fas fa-phone"></i> +212 6 54 34 27 75</li>
          <li><i class="fas fa-envelope"></i> bouichamohcine@.com</li>
          <li><i class="fas fa-clock"></i> Lun-Ven: 9h - 18h</li>
        </ul>
      </div>
    </div>
    <hr class="mb-footer-hr">
    <div class="mb-footer-copy">
      &copy; <?= date('Y') ?> BOUTIQUE MB. Tous droits réservés.
    </div>
  </div>
</footer>
<!-- Modern Footer End -->

</div>

<!-- Ajoute ce bouton panier flottant juste avant la balise </body> -->
<button id="panierBtn" title="Voir le panier" style="
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 9999;
    background: #7c3aed;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 62px;
    height: 62px;
    box-shadow: 0 4px 18px #7c3aed44;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.1rem;
    cursor: pointer;
    transition: background 0.2s, transform 0.18s;
">
    <i class="fas fa-shopping-cart"></i>
</button>
<script>
document.getElementById('panierBtn').onclick = function() {
    window.location.href = 'mes_achats.php';
};
</script>

<script>
  AOS.init({
    duration: 700,
    once: true
  });
  function toggleWishlist(btn) {
    const idProduit = btn.getAttribute('data-id');
    const isAdded = btn.classList.contains('added');
    fetch('wishlist_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_produit=' + encodeURIComponent(idProduit) + '&action=' + (isAdded ? 'remove' : 'add')
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            btn.classList.toggle('added');
            btn.querySelector('.wishlist-text').textContent = btn.classList.contains('added')
                ? ''
                : '';
        } else {
            alert("Erreur : " + data.message);
        }
    });
}

function viewWishlist() {
    // Ici tu peux ajouter le code pour afficher la wishlist, par exemple en redirigeant vers une page de la wishlist
    window.location.href = 'wishlist.php';
}

// Gestion du mode sombre
const darkModeToggle = document.getElementById('darkModeToggle');

// Set icon and mode on page load
function applyDarkModeFromStorage() {
    if (localStorage.getItem('darkMode') === '1') {
        document.body.classList.add('dark-mode');
        darkModeToggle.querySelector('i').className = 'fas fa-sun';
    } else {
        document.body.classList.remove('dark-mode');
        darkModeToggle.querySelector('i').className = 'fas fa-moon';
    }
}
applyDarkModeFromStorage();

darkModeToggle.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', isDark ? '1' : '0');
    const icon = darkModeToggle.querySelector('i');
    icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
});


function scrollToProducts() {
    // Updated selector to match your actual products grid
    const productsSection = document.querySelector('.row.row-cols-1.row-cols-md-5');
    if (productsSection) {
        const offset = 120; // Adjust this value to account for fixed header
        const elementPosition = productsSection.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
}

document.querySelectorAll('.categories-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        fetchAndReplace(this.href);
        // Update active class
        document.querySelectorAll('.categories-nav a').forEach(a => a.classList.remove('active'));
        this.classList.add('active');
    });
});

document.querySelectorAll('.categories-nav button[name="order"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        // Build URL with current category and this order
        let url = new URL(window.location.href);
        url.searchParams.set('order', this.value);
        // If a category is active, keep it in the URL
        let activeCat = document.querySelector('.categories-nav a.active');
        if (activeCat && activeCat.href.includes('categorie=')) {
            let cat = new URL(activeCat.href).searchParams.get('categorie');
            url.searchParams.set('categorie', cat);
        }
        fetchAndReplace(url.toString());
        // Update active class
        document.querySelectorAll('.categories-nav button[name="order"]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

function fetchAndReplace(url) {
    fetch(url)
        .then(res => res.text())
        .then(html => {
            // Extract only the products grid from the response
            let temp = document.createElement('div');
            temp.innerHTML = html;
            let newGrid = temp.querySelector('#productsGrid');
            if (newGrid) {
                document.getElementById('productsGrid').innerHTML = newGrid.innerHTML;
                window.history.pushState({}, '', url); // Update URL without reload
            }
        });
}
</script>
</body>
</html>