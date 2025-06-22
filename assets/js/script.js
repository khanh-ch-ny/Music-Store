// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });

    // Update cart quantity
    const quantityInputs = document.querySelectorAll('.cart-quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const quantity = this.value;
            updateCartQuantity(productId, quantity);
        });
    });

    // Remove from cart
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            removeFromCart(productId);
        });
    });

    // Search functionality
    const searchInput = document.querySelector('#search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value;
            searchProducts(searchTerm);
        }, 300));
    }

    // Category filter
    const categorySelect = document.querySelector('#category-filter');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const category = this.value;
            filterByCategory(category);
        });
    }

    // Search Overlay
    const searchOverlay = document.querySelector('.search-overlay');
    const openSearchBtn = document.querySelector('#open-search-btn');
    const closeSearchBtn = document.querySelector('.close-search-btn');
    const searchInputOverlay = document.querySelector('.search-overlay-input');

    if (searchOverlay && openSearchBtn && closeSearchBtn) {
        openSearchBtn.addEventListener('click', () => {
            searchOverlay.classList.add('active');
            // Focus the input field when overlay is opened
            setTimeout(() => searchInputOverlay.focus(), 300); 
        });

        closeSearchBtn.addEventListener('click', () => {
            searchOverlay.classList.remove('active');
        });

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
                searchOverlay.classList.remove('active');
            }
        });
    }

    // New Interactive Cart Page Logic
    const cartContainer = document.querySelector('.cart-page-container');
    if (cartContainer) {
        cartContainer.addEventListener('click', e => {
            if (e.target.classList.contains('quantity-btn')) {
                const button = e.target;
                const productId = button.dataset.productId;
                const quantityInput = cartContainer.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                let currentQuantity = parseInt(quantityInput.value);

                if (button.classList.contains('plus')) {
                    currentQuantity++;
                } else if (button.classList.contains('minus') && currentQuantity > 1) {
                    currentQuantity--;
                }
                
                quantityInput.value = currentQuantity;
                updateCartItem(productId, currentQuantity);
            }

            if (e.target.classList.contains('remove-item-btn')) {
                const button = e.target;
                const productId = button.dataset.productId;
                if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                    removeCartItem(productId);
                }
            }
        });
    }

    // Desktop Navigation Dropdowns
    const navItems = document.querySelectorAll('.desktop-nav .has-dropdown');

    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Close other open dropdowns
            navItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });
            // Toggle current dropdown
            item.classList.toggle('active');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.has-dropdown')) {
            navItems.forEach(item => {
                item.classList.remove('active');
            });
        }
    });

    // Cart Drawer (Off-canvas)
    const cartIcon = document.querySelector('.cart-icon-wrapper');
    const cartDrawer = document.getElementById('cart-drawer');
    const cartDrawerOverlay = document.getElementById('cart-drawer-overlay');
    const cartDrawerClose = document.getElementById('cart-drawer-close');
    const cartDrawerContent = document.getElementById('cart-drawer-content');

    function openCartDrawer() {
        cartDrawer.classList.add('active');
        cartDrawerOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        // Load mini cart content
        if (cartDrawerContent) {
            fetch('/Music-Store/includes/ajax/mini_cart.php')
                .then(res => res.text())
                .then(html => {
                    cartDrawerContent.innerHTML = html;
                });
        }
    }
    function closeCartDrawer() {
        cartDrawer.classList.remove('active');
        cartDrawerOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (cartIcon && cartDrawer && cartDrawerOverlay && cartDrawerClose) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            openCartDrawer();
        });
        cartDrawerOverlay.addEventListener('click', closeCartDrawer);
        cartDrawerClose.addEventListener('click', closeCartDrawer);
    }

    // Mini cart: tăng/giảm/xóa sản phẩm
    document.addEventListener('click', function(e) {
        // Tăng số lượng
        if (e.target.classList.contains('cart-drawer-qty-btn')) {
            e.preventDefault();
            const btn = e.target;
            const productId = btn.dataset.productId;
            const isPlus = btn.classList.contains('plus');
            const qtyInput = btn.parentElement.querySelector('.cart-drawer-qty-input');
            let qty = parseInt(qtyInput.value);
            if (isPlus) qty++;
            else if (qty > 1) qty--;
            // Gửi AJAX cập nhật
            fetch('/Music-Store/includes/ajax/update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=${qty}`
            })
            .then(res => res.json())
            .then(data => {
                openCartDrawer(); // reload lại mini cart
                updateHeaderCartCount(data.cart_count);
            });
        }
        // Xóa sản phẩm
        if (e.target.classList.contains('cart-drawer-remove')) {
            e.preventDefault();
            const productId = e.target.dataset.productId;
            fetch('/Music-Store/includes/ajax/remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(res => res.json())
            .then(data => {
                openCartDrawer(); // reload lại mini cart
                updateHeaderCartCount(data.cart_count);
            });
        }
    });
});

// Add to cart function
function addToCart(productId) {
    fetch('includes/ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart successfully!', 'success');
            updateCartCount(data.cartCount);
        } else {
            showNotification(data.message || 'Error adding product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding product to cart', 'error');
    });
}

// Update cart quantity function
function updateCartQuantity(productId, quantity) {
    fetch('includes/ajax/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartTotal(data.total);
            showNotification('Cart updated successfully!', 'success');
        } else {
            showNotification(data.message || 'Error updating cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating cart', 'error');
    });
}

// Remove from cart function
function removeFromCart(productId) {
    fetch('includes/ajax/remove_from_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartItem = document.querySelector(`[data-cart-item="${productId}"]`);
            if (cartItem) {
                cartItem.remove();
            }
            updateCartTotal(data.total);
            updateCartCount(data.cartCount);
            showNotification('Product removed from cart', 'success');
        } else {
            showNotification(data.message || 'Error removing product from cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error removing product from cart', 'error');
    });
}

// Search products function
function searchProducts(searchTerm) {
    fetch(`includes/ajax/search_products.php?term=${encodeURIComponent(searchTerm)}`)
    .then(response => response.json())
    .then(data => {
        const productGrid = document.querySelector('.product-grid');
        if (productGrid) {
            productGrid.innerHTML = data.html;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Filter by category function
function filterByCategory(category) {
    fetch(`includes/ajax/filter_products.php?category=${encodeURIComponent(category)}`)
    .then(response => response.json())
    .then(data => {
        const productGrid = document.querySelector('.product-grid');
        if (productGrid) {
            productGrid.innerHTML = data.html;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Update cart count in header
function updateCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Update cart total
function updateCartTotal(total) {
    const cartTotalElement = document.querySelector('.cart-total');
    if (cartTotalElement) {
        cartTotalElement.textContent = `Total: ${formatPrice(total)}`;
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            showFieldError(field, 'This field is required');
        } else {
            field.classList.remove('error');
            removeFieldError(field);
        }
    });

    return isValid;
}

// Show field error
function showFieldError(field, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

// Remove field error
function removeFieldError(field) {
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Mobile Menu Toggle
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const navLinks = document.querySelector('.nav-links');

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });
}

// Close mobile menu when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.navbar') && navLinks.classList.contains('active')) {
        navLinks.classList.remove('active');
    }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            // Close mobile menu after clicking
            if (navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
            }
        }
    });
});

// Add to cart animation
const addToCartForms = document.querySelectorAll('.add-to-cart-form');
addToCartForms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const button = this.querySelector('button');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-cart-plus"></i>';
            }, 1000);
        }, 500);
    });
});

// Product image hover effect
const productCards = document.querySelectorAll('.product-card');
productCards.forEach(card => {
    const image = card.querySelector('.product-image img');
    if (image) {
        card.addEventListener('mouseenter', () => {
            image.style.transform = 'scale(1.1)';
        });
        card.addEventListener('mouseleave', () => {
            image.style.transform = 'scale(1)';
        });
    }
});

// Form validation
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng điền đầy đủ thông tin bắt buộc.');
        }
    });
});

// Add error class styling
const style = document.createElement('style');
style.textContent = `
    .error {
        border-color: #e74c3c !important;
    }
    .error:focus {
        box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
    }
`;
document.head.appendChild(style);

// Lazy loading for images
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
});

// Sticky header
const header = document.querySelector('.header');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll <= 0) {
        header.classList.remove('scroll-up');
        return;
    }
    
    if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
        // Scroll down
        header.classList.remove('scroll-up');
        header.classList.add('scroll-down');
    } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
        // Scroll up
        header.classList.remove('scroll-down');
        header.classList.add('scroll-up');
    }
    lastScroll = currentScroll;
});

function updateCartItem(productId, quantity) {
    fetch('/Music-Store/includes/ajax/update_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartPage(data);
            updateHeaderCartCount(data.cart_count);
        } else {
            alert(data.message || 'Lỗi cập nhật giỏ hàng.');
        }
    })
    .catch(console.error);
}

function removeCartItem(productId) {
    fetch('/Music-Store/includes/ajax/remove_from_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const itemElement = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
            if (itemElement) {
                itemElement.remove();
            }
            updateCartPage(data);
            updateHeaderCartCount(data.cart_count);

            // Check if cart is now empty
            if (data.cart_count === 0) {
                document.querySelector('.cart-layout').innerHTML = `
                    <div class="empty-cart-container">
                        <img src="/Music-Store/assets/images/backgrounds/empty-cart.svg" alt="Giỏ hàng trống" class="empty-cart-image">
                        <h2>Giỏ hàng của bạn đang trống</h2>
                        <p>Hãy thêm sản phẩm vào giỏ hàng nhé.</p>
                        <a href="/Music-Store/products.php" class="btn-primary">Bắt đầu mua sắm</a>
                    </div>`;
            }
        } else {
            alert(data.message || 'Lỗi xóa sản phẩm.');
        }
    })
    .catch(console.error);
}

function updateCartPage(data) {
    const { item_total, cart_total } = data;
    
    // Update item total
    if (item_total) {
        const itemTotalElement = document.querySelector(`.item-total[data-product-id="${item_total.product_id}"]`);
        if (itemTotalElement) {
            itemTotalElement.textContent = formatPrice(item_total.total);
        }
    }

    // Update summary
    const subtotalElement = document.getElementById('summary-subtotal');
    const totalElement = document.getElementById('summary-total');
    if (subtotalElement && totalElement) {
        subtotalElement.textContent = formatPrice(cart_total);
        totalElement.textContent = formatPrice(cart_total);
    }
}

function updateHeaderCartCount(count) {
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        cartCountElement.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Banner Slider Logic
(function() {
    const slides = document.querySelectorAll('.banner-slide');
    const bg = document.getElementById('bannerSliderBg');
    const dots = document.querySelectorAll('.timer-dot');
    const circles = document.querySelectorAll('.timer-circle');
    const SLIDE_TIME = 5000;
    let current = 0;
    let timer = null;
    let startTime = null;
    let animFrame = null;

    function setSlide(idx, instant) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === idx);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === idx);
        });
        // Set background
        if (bg && slides[idx]) {
            bg.style.backgroundImage = `url('${slides[idx].src}')`;
        }
        // Reset timer circles
        circles.forEach((c, i) => {
            c.style.strokeDashoffset = i === idx ? 75.4 : 75.4;
        });
        if (!instant) animateTimer(idx);
    }

    function animateTimer(idx) {
        let start = performance.now();
        function frame(now) {
            let elapsed = now - start;
            let progress = Math.min(elapsed / SLIDE_TIME, 1);
            circles[idx].style.strokeDashoffset = 75.4 * (1 - progress);
            if (progress < 1) {
                animFrame = requestAnimationFrame(frame);
            } else {
                nextSlide();
            }
        }
        cancelAnimationFrame(animFrame);
        animFrame = requestAnimationFrame(frame);
    }

    function nextSlide() {
        current = (current + 1) % slides.length;
        setSlide(current);
    }

    function jumpTo(idx) {
        current = idx;
        setSlide(current);
    }

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            jumpTo(i);
        });
    });

    // Init
    setSlide(0, true);
    animateTimer(0);
})(); 