// Sidebar Toggle Functionality
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".dashboard-sidebar");
  const toggleBtn = document.querySelector(".sidebar-toggle");

  if (sidebar && toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("collapsed");

      // Save the sidebar state to localStorage
      const isCollapsed = sidebar.classList.contains("collapsed");
      localStorage.setItem("sidebarCollapsed", isCollapsed);
    });

    // Check localStorage for saved sidebar state
    const savedState = localStorage.getItem("sidebarCollapsed");
    if (savedState === "true") {
      sidebar.classList.add("collapsed");
    }
  }
});
