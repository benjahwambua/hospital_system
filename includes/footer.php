<?php
// includes/footer.php
?>
    </main> </div> <style>
    /* SYNC WITH SIDEBAR BLUE */
    :root {
        --sidebar-blue: #004a99; /* Your Strong Sidebar Blue */
        --sidebar-width: 250px; 
    }

    .site-footer {
        /* Align with the main content area */
        margin-left: var(--sidebar-width); 
        
        /* THEME CHANGE: Background is now Blue */
        background-color: var(--sidebar-blue);
        color: rgba(255, 255, 255, 0.8); /* Soft white text */
        
        padding: 20px 40px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        
        /* Subtle top border to separate from main content if needed */
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
    }

    .footer-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    /* Left Side: Branding */
    .footer-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .hospital-tag-white {
        background-color: rgba(255, 255, 255, 0.15); /* Translucent white box */
        color: #ffffff;
        padding: 5px 12px;
        border-radius: 4px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 11px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .copyright-span {
        font-weight: 500;
        color: #ffffff;
    }

    /* Right Side: Info & Support */
    .footer-right-info {
        display: flex;
        gap: 25px;
        align-items: center;
    }

    .support-pill-dark {
        background: rgba(0, 0, 0, 0.2); /* Darker blue contrast */
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 6px 16px;
        border-radius: 50px;
        color: #ffffff;
        font-weight: 700;
        font-size: 12px;
    }

    .dev-link-white {
        color: #ffffff;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s;
        border-bottom: 1px solid rgba(255, 255, 255, 0.4);
    }

    .dev-link-white:hover {
        color: #b3d7ff;
        border-bottom-color: #ffffff;
    }

    /* Responsive: Margin reset for mobile */
    @media (max-width: 768px) {
        .site-footer {
            margin-left: 0;
            padding: 20px;
            text-align: center;
        }
        .footer-flex {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-flex">
        <div class="footer-brand">
            <div class="hospital-tag-white">
                <?php echo htmlspecialchars($SITE_NAME ?? 'EMAQURE'); ?>
            </div>
            <span class="copyright-span">
                &copy; <?php echo date('Y'); ?> All Rights Reserved
            </span>
        </div>

        <div class="footer-right-info">
            <div class="support-pill-dark">
                <i class="fas fa-headset mr-2 text-info"></i> Support: 0705259931
            </div>
            <div class="credit-text text-white-50">
                Powered by <a href="www.flexiscriptlab.africa" class="dev-link-white">FlexiScript Labs.</a>
            </div>
        </div>
    </div>
</footer>

<script>
    /**
     * Sidebar Highlight Sync
     */
    document.addEventListener("DOMContentLoaded", function() {
        const path = window.location.pathname.split("/").pop();
        const links = document.querySelectorAll('.sidebar a');
        
        links.forEach(l => {
            if (l.getAttribute('href') === path) {
                l.style.backgroundColor = "rgba(255, 255, 255, 0.2)";
                l.style.color = "#ffffff";
                l.style.fontWeight = "bold";
                l.style.borderLeft = "4px solid #ffffff";
                l.classList.add('active');
            }
        });
    });
</script>

</body>
</html>