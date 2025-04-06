function showSubMenu(subMenuId) {
    const subMenu = document.getElementById(subMenuId);
    subMenu.style.display = "block"; // Show the submenu
}

function toggleSubMenu(subMenuId) {
    const subMenu = document.getElementById(subMenuId);
    if (subMenu.style.display === "none" || subMenu.style.display === "") {
        subMenu.style.display = "block"; // Show the submenu
    } else {
        subMenu.style.display = "none"; // Hide the submenu
    }
}