# Yuncha Valley Dashboard Architecture Guide

## Overview
The Yuncha Valley Dashboard has been fully migrated to a **Component-Based Architecture**. This update standardizes the file structure, removes monolithic boilerplate (repetitive HTML/Head tags, Sidebar, and Navigation logic), and dynamically injects page-specific configurations (like titles, CSS, and JS) into global templates.

## Core Components
All core management pages (`dashboard.php`, `bookings.php`, `rooms.php`, `frontdesk.php`, `finance.php`, `settings.php`, etc.) must now adhere to the following structure utilizing the `components/` directory:

1. **`components/header.php`**: Contains the `<!DOCTYPE html>`, `<head>`, CSS library imports, `<body>` tag, `sidebar` logic, and the top navigation bar.
2. **`components/footer.php`**: Contains the closing `</body>`, `</html>`, and global scripts (like Sidebar toggling and GSAP animations).

## Page Structure Template
When creating a new page or modifying an existing one, use the following template:

```php
<?php
// 1. Initial Logic & Security
require_once 'config/security.php';
require_once 'config/db.php';

// 2. Define Header Variables
$page_title = 'Page Name | Yuncha Valley';
$header_title = 'PAGE <span class="text-transparent bg-clip-text bg-gradient-to-r from-gray-200 to-gray-500">NAME</span>';
$header_desc = 'Short description of the page';

// Optional: If the page needs a specific accent color or wants to skip the topbar
// $selection_color = 'bg-ycBlue'; 
// $skip_topbar = false; 

// 3. Inject Page-Specific CSS (Optional)
ob_start();
?>
<style>
    /* Custom styles for this page only */
    .custom-element { color: #ff003c; }
</style>
<?php
$extra_head = ob_get_clean();

// 4. Include the Header Component
include __DIR__ . '/components/header.php';
?>

<!-- 5. Main Content -->
<main class="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth">
    <div class="max-w-7xl mx-auto space-y-6 pb-12 gs-anim">
        <!-- Page Content Goes Here -->
    </div>
</main>
</div> <!-- Closes the flex-1 container started in header.php -->

<!-- 6. Modals (Optional) -->
<div id="customModal" class="hidden fixed inset-0 z-50 ...">
    <!-- Modal Content -->
</div>

<!-- 7. Inject Page-Specific JS -->
<?php ob_start(); ?>
<script>
    // Custom logic, event listeners, or charting logic
    console.log("Page specific logic loaded");
</script>
<?php 
$extra_js = ob_get_clean(); 

// 8. Include the Footer Component
include __DIR__ . '/components/footer.php'; 
?>
```

## Styling & Aesthetic Guidelines
*   **Neon Theme**: Use the `neon-pro` class combined with inline CSS variable `--neon-color: #HEX` to apply the glowing glassmorphism aesthetic.
*   **Available Neon Colors**:
    *   `#000000` (ycDeep)
    *   `#00ff41` (ycGreen)
    *   `#ffcc00` (ycGold)
    *   `#00d0ff` (ycBlue)
    *   `#ff00ff` (ycPink)
    *   `#ff003c` (ycRed)
    *   `#ff5e00` (ycOrange)
    *   `#b537f2` (ycPurple)
*   **Modals**: Ensure modals are placed *after* the `</main></div>` and *before* the `<script>` / `footer.php` inclusion so they render optimally outside of the scrolling container.

## Important Notes on Form Elements
*   **Custom Selects**: The global `settings.php` and `rooms.php` utilize a JavaScript MutationObserver polyfill to automatically theme standard `<select>` tags into a custom UI. Any dynamic inserts of `<select>` tags are tracked and auto-rendered, provided they include Tailwind classes starting with `input-dark` or `bg-`.
*   **Alerts**: The system uses a globally styled Success Alert logic. Trigger standard alerts dynamically if needed, as built into the respective modules.
