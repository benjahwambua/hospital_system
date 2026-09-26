<?php
// includes/footer.php
?>
</main>
</div>
<!-- Final HMS design layer: loaded after page-level styles so shared controls remain visually consistent. -->
<link rel="stylesheet" href="<?php echo $ASSETS_PATH; ?>/css/style.css">
<footer class="site-footer">
  <div class="footer-flex">
    <div class="footer-brand">
      <div class="hospital-tag-white"><?php echo htmlspecialchars($SITE_NAME ?? 'EMAQURE'); ?></div>
      <span class="copyright-span">&copy; <?php echo date('Y'); ?> All Rights Reserved</span>
    </div>
    <div class="footer-right-info">
      <div class="support-pill-dark"><i class="fas fa-headset mr-2"></i> Support: 0705259931</div>
      <div class="credit-text">Powered by <a href="https://www.flexiscriptlab.africa" class="dev-link-white">FlexiScript Labs.</a></div>
    </div>
  </div>
</footer>
<script>
document.addEventListener("DOMContentLoaded", function(){
  const sidebar=document.querySelector(".sidebar");
  const navToggle=document.querySelector(".mobile-nav-toggle");
  if(navToggle && sidebar){ navToggle.addEventListener("click",()=>sidebar.classList.toggle("mobile-open")); }
  const toggles=document.querySelectorAll(".menu-toggle");
  toggles.forEach(toggle=>toggle.addEventListener("click",function(e){
    e.preventDefault();
    const parent=this.parentElement;
    document.querySelectorAll(".has-submenu").forEach(item=>{if(item!==parent)item.classList.remove("open");});
    parent.classList.toggle("open");
  }));
  const current=window.location.pathname;
  document.querySelectorAll(".sidebar a").forEach(link=>{
    const href=link.getAttribute("href")||"";
    if(href && href!=="#" && current.indexOf(href.replace("/hospital_system",""))!==-1) link.classList.add("active");
  });
});
</script>
</body>
</html>