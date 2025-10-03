<div class="header">
    <div class="header-content">
        <a href="/">
                   <div class="logo">
            <span class="logo-icon">☕️</span>
            <span class="logo-text">Simple Starbucks</span>
        </div>
        </a>
 
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-section">
                <div class="user-info-dropdown">
                    <div class="user-trigger">
                        <span class="user-avatar">👤</span>
                        <span class="user-name"><?php echo $_SESSION['username']; ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </div>
                    
                    <div class="user-dropdown-content">
                        <div class="user-details">
                            <div class="user-detail-item">
                                <span class="detail-label">Username:</span>
                                <span class="detail-value"><?php echo $_SESSION['username']; ?></span>
                            </div>
                            <div class="user-detail-item">
                                <span class="detail-label">Role:</span>
                                <span class="role-badge"><?php echo $_SESSION['role']; ?></span>
                            </div>
                        </div>
                        
                        <div class="dropdown-divider"></div>
                        
                        <div class="dropdown-links">
                            <a href="/board/profile.php" class="dropdown-link">
                                <span class="link-icon">👤</span>
                                My Profile
                            </a>
                            <a href="/auth/logout.php" class="dropdown-link">
                                <span class="link-icon">🚪</span>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
