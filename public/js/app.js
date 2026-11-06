document.addEventListener('DOMContentLoaded', () => {
    const themeToggles = Array.from(document.querySelectorAll('[data-theme-toggle]'));

    const applyTheme = (theme) => {
        document.documentElement.dataset.theme = theme;
        document.documentElement.dataset.bsTheme = theme;

        try {
            localStorage.setItem('brewgo-theme', theme);
        } catch (error) {
        }

        themeToggles.forEach((button) => {
            button.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
            button.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            button.setAttribute('title', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        });
    };

    applyTheme(document.documentElement.dataset.theme === 'light' ? 'light' : 'dark');

    themeToggles.forEach((button) => {
        button.addEventListener('click', () => {
            applyTheme(document.documentElement.dataset.theme === 'light' ? 'dark' : 'light');
        });
    });

    window.addEventListener('storage', (event) => {
        if (event.key === 'brewgo-theme' && ['light', 'dark'].includes(event.newValue)) {
            applyTheme(event.newValue);
        }
    });

    const sidebarToggle = document.querySelector('[data-admin-sidebar-toggle]');

    if (sidebarToggle) {
        const root = document.documentElement;

        const syncSidebarToggle = () => {
            const collapsed = root.classList.contains('admin-sidebar-collapsed');
            const label = collapsed ? 'Expand sidebar' : 'Collapse sidebar';

            sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
            sidebarToggle.setAttribute('aria-label', label);
            sidebarToggle.setAttribute('title', label);
        };

        syncSidebarToggle();

        sidebarToggle.addEventListener('click', () => {
            root.classList.toggle('admin-sidebar-collapsed');

            try {
                localStorage.setItem(
                    'brewgo-admin-sidebar',
                    root.classList.contains('admin-sidebar-collapsed') ? 'collapsed' : 'expanded'
                );
            } catch (error) {
            }

            syncSidebarToggle();
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.closest('.password-field')?.querySelector('input');

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            const icon = button.querySelector('i');

            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            button.setAttribute('title', isVisible ? 'Show password' : 'Hide password');

            if (icon) {
                icon.classList.toggle('bi-eye', isVisible);
                icon.classList.toggle('bi-eye-slash', !isVisible);
            }
        });
    });

    const normalizeMenuText = (value) => String(value ?? '')
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    document.querySelectorAll('[data-product-filter]').forEach((filter) => {
        const search = filter.querySelector('[data-product-search]');
        const clearSearch = filter.querySelector('[data-product-search-clear]');
        const items = Array.from(filter.querySelectorAll('[data-product-item]'));
        const count = filter.querySelector('[data-product-count]');
        const emptyState = filter.querySelector('[data-product-empty]');
        const resetButtons = Array.from(filter.querySelectorAll('[data-product-reset]'));
        const form = filter.querySelector('[data-product-filter-form]');
        const categoryButtons = Array.from(filter.querySelectorAll('[data-menu-category]'));

        if (!search || !items.length) return;

        let activeCategory = categoryButtons
            .find((button) => button.classList.contains('is-active'))
            ?.dataset.menuCategory || '';

        const currentUrl = new URL(window.location.href);

        if (!search.value && currentUrl.searchParams.has('search')) {
            search.value = currentUrl.searchParams.get('search');
        }

        const syncCategoryButtons = () => {
            categoryButtons.forEach((categoryButton) => {
                const isActive = (categoryButton.dataset.menuCategory || '') === activeCategory;

                categoryButton.classList.toggle('is-active', isActive);

                if (isActive) {
                    categoryButton.setAttribute('aria-current', 'page');
                } else {
                    categoryButton.removeAttribute('aria-current');
                }
            });
        };

        const applySearch = ({ updateUrl = true } = {}) => {
            const query = normalizeMenuText(search.value.trim());
            let visible = 0;

            items.forEach((item) => {
                const matchesSearch = !query || normalizeMenuText(item.dataset.productSearchText).includes(query);
                const matchesCategory = !activeCategory || item.dataset.productCategory === activeCategory;
                const show = matchesSearch && matchesCategory;

                item.classList.toggle('d-none', !show);

                if (show) {
                    visible += 1;
                }
            });

            clearSearch?.classList.toggle('d-none', !search.value.trim());
            emptyState?.classList.toggle('d-none', visible !== 0);
            count?.classList.toggle('d-none', visible === 0);

            if (count) {
                count.textContent = `Showing 1–${visible} of ${visible} ${visible === 1 ? 'item' : 'items'}`;
            }

            if (updateUrl) {
                const url = new URL(window.location.href);

                if (search.value.trim()) {
                    url.searchParams.set('search', search.value.trim());
                } else {
                    url.searchParams.delete('search');
                }

                if (activeCategory) {
                    url.searchParams.set('category', activeCategory);
                } else {
                    url.searchParams.delete('category');
                }

                try {
                    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
                } catch (error) {
                }
            }
        };

        search.addEventListener('input', () => applySearch());

        search.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && search.value) {
                search.value = '';
                applySearch();
            }
        });

        clearSearch?.addEventListener('click', () => {
            search.value = '';
            applySearch();
            search.focus();
        });

        categoryButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                activeCategory = button.dataset.menuCategory || '';
                syncCategoryButtons();
                applySearch();
            });
        });

        resetButtons.forEach((button) => {
            button.addEventListener('click', () => {
                search.value = '';
                activeCategory = '';
                syncCategoryButtons();
                applySearch();
                search.focus();
            });
        });

        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            applySearch();
        });

        applySearch({ updateUrl: false });
    });

    const navbar = document.querySelector('.navbar-bp');

    if (navbar) {
        const updateNavbar = () => navbar.classList.toggle('scrolled', window.scrollY > 24);
        updateNavbar();
        window.addEventListener('scroll', updateNavbar, { passive: true });
    }

    const sectionLinks = Array.from(document.querySelectorAll('[data-section-link]'));
    const sectionTargets = sectionLinks
        .map((link) => document.getElementById(link.dataset.sectionLink))
        .filter(Boolean);

    if (sectionTargets.length) {
        const updateSectionNavigation = () => {
            const marker = window.scrollY + (navbar?.offsetHeight ?? 0) + 96;
            let current = sectionTargets[0].id;

            sectionTargets.forEach((section) => {
                if (section.getBoundingClientRect().top + window.scrollY <= marker) {
                    current = section.id;
                }
            });

            sectionLinks.forEach((link) => {
                link.classList.toggle('active', link.dataset.sectionLink === current);
            });
        };

        updateSectionNavigation();
        window.addEventListener('scroll', updateSectionNavigation, { passive: true });
        window.addEventListener('resize', updateSectionNavigation);
        window.addEventListener('hashchange', updateSectionNavigation);
    }

    const mainNav = document.getElementById('mainNav');

    if (mainNav) {
        mainNav.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => {
                if (mainNav.classList.contains('show') && window.bootstrap?.Collapse) {
                    window.bootstrap.Collapse.getOrCreateInstance(mainNav).hide();
                }
            });
        });
    }

    document.querySelectorAll('[data-qty]').forEach((control) => {
        const input = control.querySelector('input');
        const minus = control.querySelector('[data-qty-minus]');
        const plus = control.querySelector('[data-qty-plus]');
        const form = control.closest('form');

        if (!input) return;

        const min = parseInt(input.min || '1', 10);
        const max = parseInt(input.max || '20', 10);

        const sanitize = () => {
            const digits = input.value.replace(/\D/g, '');

            if (digits !== input.value) {
                input.value = digits;
            }

            return digits === '' ? null : Number.parseInt(digits, 10);
        };

        const renderButtons = () => {
            const value = sanitize();

            if (minus) {
                minus.disabled = value !== null && value <= min;
            }

            if (plus) {
                plus.disabled = value !== null && value >= max;
            }
        };

        const setValue = (value) => {
            input.value = String(Math.min(max, Math.max(min, value)));
            renderButtons();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        };

        const step = (direction) => {
            const value = sanitize();

            setValue((value ?? min) + direction);
        };

        const commit = () => setValue(sanitize() ?? min);

        minus?.addEventListener('click', () => step(-1));
        plus?.addEventListener('click', () => step(1));

        input.addEventListener('input', () => {
            const value = sanitize();

            if (value !== null && value > max) {
                input.value = String(max);
            }

            renderButtons();
        });

        input.addEventListener('change', commit);

        form?.addEventListener('submit', () => {
            const value = sanitize();

            if (value === null || value < min || value > max) {
                setValue(value === null ? min : value);
            }
        });

        renderButtons();
    });

    const formatCartMoney = (value) => '₱' + Number(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const refreshCartBadge = (uniqueItemCount = null) => {
        document.querySelectorAll('[data-cart-count]').forEach((badge) => {
            if (uniqueItemCount !== null) {
                badge.textContent = String(uniqueItemCount);
            }

            const count = Number.parseInt(badge.textContent || '0', 10);
            badge.classList.toggle('d-none', count <= 0);

            const link = badge.closest('a');

            if (link) {
                link.setAttribute('aria-label', `Cart, ${count} ${count === 1 ? 'product' : 'products'}`);
            }
        });
    };

    const refreshCartSubtotals = (subtotal) => {
        document.querySelectorAll('[data-cart-subtotal], [data-cart-total]').forEach((element) => {
            element.textContent = formatCartMoney(subtotal);
        });
    };

    const cartQuantityForms = Array.from(document.querySelectorAll('[data-cart-quantity-form]'));

    if (cartQuantityForms.length) {
        const cartItems = Array.from(document.querySelectorAll('[data-cart-item]'));

        const refreshCartTotals = (payload = null) => {
            const selectedItems = cartItems.filter((item) => item.dataset.selected === '1');
            const cartSubtotal = payload
                ? Number(payload.subtotal || 0)
                : selectedItems.reduce(
                    (total, item) => total + Number(item.dataset.lineTotal || 0),
                    0
                );
            const uniqueCount = payload
                ? Number(payload.unique_item_count || 0)
                : new Set(cartItems.map((item) => item.dataset.productId)).size;
            const selectedQuantity = payload
                ? Number(payload.selected_total_quantity || 0)
                : selectedItems.reduce((total, item) => total + Number(item.dataset.quantity || 0), 0);
            const totalQuantity = payload
                ? Number(payload.total_quantity || 0)
                : cartItems.reduce((total, item) => total + Number(item.dataset.quantity || 0), 0);
            const hasSelection = payload
                ? Boolean(payload.has_selection)
                : selectedItems.length > 0;

            refreshCartSubtotals(cartSubtotal);
            refreshCartBadge(uniqueCount);

            document.querySelectorAll('[data-cart-total-quantity]').forEach((element) => {
                element.textContent = uniqueCount + ' ' + (uniqueCount === 1 ? 'product' : 'products');
            });

            document.querySelectorAll('[data-cart-selected-summary]').forEach((element) => {
                element.textContent = selectedQuantity + ' of ' + totalQuantity + ' '
                    + (totalQuantity === 1 ? 'item' : 'items');
            });

            document.querySelectorAll('[data-checkout-button]').forEach((button) => {
                button.disabled = !hasSelection;
            });

            document.querySelectorAll('[data-cart-selection-hint]').forEach((element) => {
                element.textContent = hasSelection
                    ? 'Unselected items stay in your bag for later.'
                    : 'Select at least one item to checkout.';
            });
        };

        cartQuantityForms.forEach((form) => {
            const item = form.closest('[data-cart-item]');
            const decrement = form.querySelector('[data-cart-decrement]');
            const increment = form.querySelector('[data-cart-increment]');
            const quantityInput = form.querySelector('[data-cart-quantity-input]');
            const errorOutput = form.querySelector('[data-cart-quantity-error]');
            const lineTotalOutput = item?.querySelector('[data-cart-line-total]');

            if (!item || !quantityInput || !decrement || !increment) return;

            const min = 1;
            const max = 20;
            let busy = false;
            let inputTimer = null;

            const sanitizeInput = () => {
                const digits = quantityInput.value.replace(/\D/g, '');

                if (digits !== quantityInput.value) {
                    quantityInput.value = digits;
                }

                return digits === '' ? null : Number.parseInt(digits, 10);
            };

            const clearInputTimer = () => {
                if (inputTimer !== null) {
                    window.clearTimeout(inputTimer);
                    inputTimer = null;
                }
            };

            const render = () => {
                const quantity = Number(item.dataset.quantity || 1);
                const lineTotal = Number(item.dataset.lineTotal || 0);

                quantityInput.value = String(quantity);
                quantityInput.disabled = busy;

                if (lineTotalOutput) {
                    lineTotalOutput.textContent = formatCartMoney(lineTotal);
                }

                decrement.disabled = busy || quantity <= min;
                increment.disabled = busy || quantity >= max;
                form.setAttribute('aria-busy', busy ? 'true' : 'false');
            };

            const setError = (message = '') => {
                if (!errorOutput) return;

                errorOutput.textContent = message;
                errorOutput.classList.toggle('d-none', message === '');
            };

            const setQuantity = (quantity) => {
                const unitPrice = Number(item.dataset.unitPrice || 0);

                item.dataset.quantity = String(quantity);
                item.dataset.lineTotal = (unitPrice * quantity).toFixed(2);
                render();
                refreshCartTotals();
            };

            const currentQuantity = () => {
                const value = sanitizeInput();

                if (value !== null && value >= min && value <= max) {
                    return value;
                }

                return Number(item.dataset.quantity || min);
            };

            const updateTo = async (quantity) => {
                if (busy) return;

                clearInputTimer();

                const current = Number(item.dataset.quantity || min);
                const next = Math.min(max, Math.max(min, quantity));

                if (next === current) {
                    render();
                    return;
                }

                const previous = {
                    quantity: item.dataset.quantity,
                    lineTotal: item.dataset.lineTotal,
                };

                busy = true;
                setError();
                setQuantity(next);

                const formData = new FormData(form);
                formData.set('quantity', String(next));

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const message = payload.errors?.quantity?.[0] || payload.message || 'Could not update the quantity.';
                        throw new Error(message);
                    }

                    item.dataset.quantity = String(payload.item.quantity);
                    item.dataset.lineTotal = Number(payload.item.line_total).toFixed(2);
                    refreshCartTotals(payload.cart);
                } catch (error) {
                    item.dataset.quantity = previous.quantity;
                    item.dataset.lineTotal = previous.lineTotal;
                    refreshCartTotals();
                    setError(error.message || 'Could not update the quantity.');
                } finally {
                    busy = false;
                    render();
                }
            };

            const step = (direction) => {
                const value = currentQuantity();

                updateTo(value + direction);
            };

            const commitInput = () => {
                clearInputTimer();

                const value = sanitizeInput();
                const target = value === null ? min : Math.min(max, Math.max(min, value));

                quantityInput.value = String(target);
                updateTo(target);
            };

            quantityInput.addEventListener('input', () => {
                clearInputTimer();

                const value = sanitizeInput();

                if (value !== null && value > max) {
                    quantityInput.value = String(max);
                }

                if (value === null) {
                    return;
                }

                const target = Math.min(max, Math.max(min, value));

                inputTimer = window.setTimeout(() => {
                    inputTimer = null;
                    updateTo(target);
                }, 400);
            });

            quantityInput.addEventListener('change', commitInput);

            quantityInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    commitInput();
                }
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                commitInput();
            });

            decrement.addEventListener('click', () => step(-1));
            increment.addEventListener('click', () => step(1));

            render();
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        document.querySelectorAll('[data-cart-select]').forEach((checkbox) => {
            const item = checkbox.closest('[data-cart-item]');
            const errorOutput = item?.querySelector('[data-cart-selection-error]');

            if (!item) return;

            const setSelectionError = (message = '') => {
                if (!errorOutput) return;

                errorOutput.textContent = message;
                errorOutput.classList.toggle('d-none', message === '');
            };

            const applySelection = (isSelected) => {
                item.dataset.selected = isSelected ? '1' : '0';
                item.classList.toggle('is-unselected', !isSelected);
                checkbox.checked = isSelected;
            };

            checkbox.addEventListener('change', async () => {
                const nextSelected = checkbox.checked;
                const previousSelected = item.dataset.selected === '1';

                checkbox.disabled = true;
                setSelectionError();
                applySelection(nextSelected);
                refreshCartTotals();

                try {
                    const response = await fetch(checkbox.dataset.selectionUrl, {
                        method: 'PATCH',
                        body: JSON.stringify({ is_selected: nextSelected }),
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                        },
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const message = payload.errors?.is_selected?.[0] || payload.message || 'Could not update this item.';
                        throw new Error(message);
                    }

                    applySelection(Boolean(payload.item.is_selected));
                    refreshCartTotals(payload.cart);
                } catch (error) {
                    applySelection(previousSelected);
                    refreshCartTotals();
                    setSelectionError(error.message || 'Could not update this item.');
                } finally {
                    checkbox.disabled = false;
                }
            });
        });
    }

    document.querySelectorAll('[data-quick-add]').forEach((form) => {
        const button = form.querySelector('[data-quick-add-button]');
        const status = form.querySelector('[data-quick-add-status]');

        if (!button) return;

        const defaultButton = button.innerHTML;
        let resetTimer = null;

        const setStatus = (message = '', isError = false) => {
            if (!status) return;

            status.textContent = message;
            status.classList.toggle('d-none', message === '');
            status.classList.toggle('text-danger', isError);
            status.classList.toggle('text-gold', !isError && message !== '');
        };

        const resetButton = () => {
            button.innerHTML = defaultButton;
            button.classList.remove('is-added');
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            window.clearTimeout(resetTimer);

            button.disabled = true;
            resetButton();
            setStatus('Adding...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const messages = payload.errors ? Object.values(payload.errors).flat() : [];
                    throw new Error(messages[0] || payload.message || 'Could not add this item to your cart.');
                }

                refreshCartBadge(Number(payload.cart.unique_item_count));
                refreshCartSubtotals(Number(payload.cart.subtotal));
                button.innerHTML = '<i class="bi bi-check2 me-1"></i> Added';
                button.classList.add('is-added');
                setStatus(payload.message || 'Added to cart.');

                resetTimer = window.setTimeout(() => {
                    resetButton();
                    setStatus('');
                }, 2400);
            } catch (error) {
                setStatus(error.message || 'Could not add this item to your cart.', true);
                resetTimer = window.setTimeout(() => setStatus(''), 4500);
            } finally {
                button.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-customizer]').forEach((form) => {
        const basePrice = parseFloat(form.dataset.basePrice || '0');
        const quantityInput = form.querySelector('[data-customizer-qty]');
        const totalOutput = form.querySelector('[data-customizer-total]');
        const unitOutput = form.querySelector('[data-customizer-unit]');
        const sizeInputs = Array.from(form.querySelectorAll('[data-size-option]'));
        const sizePrompt = form.querySelector('[data-size-prompt]');
        const submitButton = form.querySelector('[data-add-to-cart]');
        const requiresSize = form.dataset.requiresSize === '1' || sizeInputs.length > 0;

        const refresh = () => {
            const hasSelectedSize = ! requiresSize || sizeInputs.some((input) => input.checked);

            if (! hasSelectedSize) {
                submitButton?.setAttribute('disabled', 'disabled');
                sizePrompt?.classList.remove('d-none');

                if (totalOutput) {
                    totalOutput.textContent = 'Select a size';
                }

                if (unitOutput) {
                    unitOutput.textContent = 'Select a size';
                }

                return;
            }

            submitButton?.removeAttribute('disabled');
            sizePrompt?.classList.add('d-none');

            const absoluteSelection = form.querySelector('.customizer-option:checked[data-price-mode="absolute"]');
            let unit = absoluteSelection
                ? parseFloat(absoluteSelection.dataset.price || '0')
                : basePrice;

            form.querySelectorAll('.customizer-option:checked, .customizer-addon:checked').forEach((input) => {
                if (input === absoluteSelection) return;

                unit += parseFloat(input.dataset.price || '0');
            });

            const quantity = Math.max(1, parseInt(quantityInput?.value || '1', 10));

            if (totalOutput) {
                totalOutput.textContent = '₱' + (unit * quantity).toFixed(2);
            }

            if (unitOutput) {
                unitOutput.textContent = '₱' + unit.toFixed(2);
            }
        };

        form.addEventListener('change', refresh);
        quantityInput?.addEventListener('input', refresh);
        refresh();
    });

    document.querySelectorAll('[data-service-form]').forEach((form) => {
        const radios = form.querySelectorAll('input[name="service_type"]');
        const panels = form.querySelectorAll('[data-service-panel]');

        const refresh = () => {
            const selected = form.querySelector('input[name="service_type"]:checked')?.value;

            panels.forEach((panel) => {
                const active = panel.dataset.servicePanel === selected;
                panel.classList.toggle('d-none', !active);
                panel.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = !active;
                });
            });
        };

        radios.forEach((radio) => radio.addEventListener('change', refresh));
        refresh();
    });

    document.querySelectorAll('input[name="promotion_code"]').forEach((input) => {
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-submit-once]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        const originalText = button?.innerHTML ?? '';
        let submitting = false;

        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented || submitting) {
                event.preventDefault();
                return;
            }

            submitting = true;

            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Working...';
            }
        });

        window.addEventListener('pageshow', (event) => {
            if (!event.persisted) {
                return;
            }

            submitting = false;

            if (button) {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    });

    document.querySelectorAll('[data-image-preview]').forEach((preview) => {
        const form = preview.closest('form');
        const input = form?.querySelector('input[type="file"]');
        const wrapper = preview.closest('[data-image-preview-wrap]');

        if (!input || !wrapper) {
            return;
        }

        let objectUrl = null;

        input.addEventListener('change', () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            const file = input.files?.[0];

            if (!file || !file.type.startsWith('image/')) {
                preview.removeAttribute('src');
                wrapper.classList.add('d-none');

                return;
            }

            objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            wrapper.classList.remove('d-none');
        });
    });

    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        window.setTimeout(() => {
            if (window.bootstrap?.Alert) {
                window.bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 6500);
    });

    const notificationRegion = document.querySelector('[data-notification-url]');

    if (notificationRegion) {
        const notificationBadges = Array.from(document.querySelectorAll('[data-notification-count]'));
        const notificationLabels = Array.from(document.querySelectorAll('[data-notification-count-label]'));
        const notificationUrl = notificationRegion.dataset.notificationUrl;
        let notificationBusy = false;

        const applyUnreadCount = (count) => {
            const normalized = Math.max(0, Number.parseInt(count, 10) || 0);

            notificationBadges.forEach((badge) => {
                badge.textContent = String(normalized);
                badge.classList.toggle('d-none', normalized <= 0);

                const link = badge.closest('a');

                if (link) {
                    link.setAttribute('aria-label', `Notifications, ${normalized} unread`);
                }
            });

            notificationLabels.forEach((label) => {
                label.textContent = normalized > 0 ? ` (${normalized})` : '';
                label.classList.toggle('d-none', normalized <= 0);
            });
        };

        const refreshNotificationCount = async () => {
            if (notificationBusy || document.hidden) return;

            notificationBusy = true;

            try {
                const response = await fetch(notificationUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) return;

                const payload = await response.json();
                applyUnreadCount(payload.count);
            } catch (error) {
            } finally {
                notificationBusy = false;
            }
        };

        const initialCount = Number.parseInt(notificationBadges[0]?.textContent || '0', 10);

        applyUnreadCount(initialCount);

        window.setInterval(refreshNotificationCount, 30000);
        window.addEventListener('focus', refreshNotificationCount);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                refreshNotificationCount();
            }
        });
    }

    document.querySelectorAll('[data-qr-preview-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const image = document.querySelector('[data-qr-preview-image]');
            const empty = document.querySelector('[data-qr-preview-empty]');

            if (!file || !image) return;

            image.src = URL.createObjectURL(file);
            image.classList.remove('d-none');
            empty?.classList.add('d-none');
        });
    });

    document.querySelectorAll('[data-avatar-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const preview = document.querySelector('[data-avatar-preview] .avatar-circle');

            if (!file || !preview) return;

            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = 'Selected profile picture';
            preview.replaceChildren(image);
        });
    });

    document.querySelectorAll('[data-carousel-bulk]').forEach((form) => {
        const input = form.querySelector('[data-carousel-input]');
        const preview = form.querySelector('[data-carousel-preview]');

        if (!input || !preview) return;

        let files = [];

        const syncInput = () => {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        };

        const render = () => {
            preview.replaceChildren();

            if (!files.length) {
                preview.classList.add('d-none');
                return;
            }

            preview.classList.remove('d-none');

            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'carousel-preview-item';

                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = file.name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'carousel-preview-remove';
                remove.setAttribute('aria-label', `Remove ${file.name}`);
                remove.innerHTML = '<i class="bi bi-x-lg"></i>';
                remove.addEventListener('click', () => {
                    files.splice(index, 1);
                    syncInput();
                    render();
                });

                const name = document.createElement('span');
                name.className = 'carousel-preview-name';
                name.textContent = file.name;

                item.append(image, remove, name);
                preview.appendChild(item);
            });
        };

        input.addEventListener('change', () => {
            files = Array.from(input.files || []);
            render();
        });

        form.addEventListener('reset', () => {
            files = [];
            render();
        });
    });

    const carouselSelectAll = document.querySelector('[data-carousel-select-all]');
    const carouselSelections = Array.from(document.querySelectorAll('[data-carousel-select]'));
    const carouselBulkForm = document.getElementById('carouselBulkDelete');
    const carouselBulkSubmit = document.querySelector('[data-carousel-bulk-submit]');
    const carouselSelectedCount = document.querySelector('[data-carousel-selected-count]');

    if (carouselSelections.length && carouselBulkSubmit && carouselSelectedCount) {
        const syncCarouselSelection = () => {
            const selectedCount = carouselSelections.filter((input) => input.checked).length;

            carouselSelectedCount.textContent = `${selectedCount} selected`;
            carouselBulkSubmit.disabled = selectedCount === 0;

            carouselSelections.forEach((input) => {
                input.closest('.carousel-gallery-card')?.classList.toggle('is-selected', input.checked);
            });

            if (carouselSelectAll) {
                carouselSelectAll.checked = selectedCount > 0 && selectedCount === carouselSelections.length;
                carouselSelectAll.indeterminate = selectedCount > 0 && selectedCount < carouselSelections.length;
            }

            if (carouselBulkForm) {
                carouselBulkForm.dataset.confirm = selectedCount === 1
                    ? 'Delete the selected carousel image? This cannot be undone.'
                    : `Delete the ${selectedCount} selected carousel images? This cannot be undone.`;
            }
        };

        carouselSelections.forEach((input) => {
            input.addEventListener('change', syncCarouselSelection);
        });

        carouselSelectAll?.addEventListener('change', () => {
            carouselSelections.forEach((input) => {
                input.checked = carouselSelectAll.checked;
            });

            syncCarouselSelection();
        });

        syncCarouselSelection();
    }

    document.querySelectorAll('[data-live-filter-form]').forEach((form) => {
        let timer;

        const submit = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => {
            input.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(submit, 350);
            });
        });

        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', submit);
        });

        form.addEventListener('submit', () => window.clearTimeout(timer));
    });

    const chatbot = document.querySelector('[data-chatbot]');

    if (chatbot) {
        const panel = chatbot.querySelector('[data-chatbot-panel]');
        const toggle = chatbot.querySelector('[data-chatbot-toggle]');
        const close = chatbot.querySelector('[data-chatbot-close]');
        const messages = chatbot.querySelector('[data-chatbot-messages]');
        const form = chatbot.querySelector('[data-chatbot-form]');
        const input = chatbot.querySelector('[data-chatbot-input]');
        const send = chatbot.querySelector('[data-chatbot-send]');
        const endpoint = chatbot.dataset.chatbotEndpoint;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        let busy = false;

        const scrollToLatest = () => {
            messages.scrollTop = messages.scrollHeight;
        };

        const addMessage = (text, role, links = []) => {
            const bubble = document.createElement('div');
            bubble.className = `chatbot-message chatbot-message-${role}`;
            bubble.textContent = text;

            if (links.length) {
                const linkList = document.createElement('div');
                linkList.className = 'chatbot-links';

                links.forEach((link) => {
                    const anchor = document.createElement('a');
                    anchor.className = 'chatbot-link';
                    anchor.href = link.url;
                    anchor.textContent = link.label;
                    linkList.appendChild(anchor);
                });

                bubble.appendChild(linkList);
            }

            messages.appendChild(bubble);
            scrollToLatest();

            return bubble;
        };

        const addTyping = () => {
            const bubble = document.createElement('div');
            bubble.className = 'chatbot-message chatbot-message-bot';

            const typing = document.createElement('span');
            typing.className = 'chatbot-typing';
            typing.setAttribute('role', 'status');
            typing.setAttribute('aria-label', 'Brew Buddy is typing');
            typing.innerHTML = '<span></span><span></span><span></span>';

            bubble.appendChild(typing);
            messages.appendChild(bubble);
            scrollToLatest();

            return bubble;
        };

        const setOpen = (open) => {
            chatbot.classList.toggle('is-open', open);
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close Brew Buddy' : 'Open Brew Buddy');

            if (open) {
                input.focus();
                scrollToLatest();
            }
        };

        const ask = async (text) => {
            const message = text.trim();

            if (!message || busy) {
                return;
            }

            busy = true;
            send.disabled = true;
            addMessage(message, 'user');
            input.value = '';
            const typing = addTyping();

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ message }),
                });

                if (!response.ok) {
                    throw new Error('Assistant request failed');
                }

                const payload = await response.json();
                typing.remove();
                addMessage(payload.reply || "Sorry, I didn't catch that.", 'bot', payload.links || []);
            } catch (error) {
                typing.remove();
                addMessage("I couldn't reach the counter just now. Please try again, or use the contact page to reach the team.", 'bot');
            } finally {
                busy = false;
                send.disabled = false;
                input.focus();
            }
        };

        toggle.addEventListener('click', () => setOpen(panel.hidden));
        close.addEventListener('click', () => {
            setOpen(false);
            toggle.focus();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            ask(input.value);
        });

        chatbot.querySelectorAll('[data-chatbot-quick]').forEach((button) => {
            button.addEventListener('click', () => ask(button.dataset.chatbotQuick || button.textContent));
        });

        chatbot.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !panel.hidden) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    const liveSync = document.querySelector('[data-live-sync]');

    if (liveSync) {
        const liveSyncUrl = liveSync.dataset.liveSyncUrl;
        const reloadButton = liveSync.querySelector('[data-live-sync-reload]');
        const dismissButton = liveSync.querySelector('[data-live-sync-dismiss]');
        let currentVersion = null;
        let liveSyncBusy = false;
        let liveSyncDismissed = false;

        const checkStorefrontVersion = async () => {
            if (liveSyncBusy || document.hidden) {
                return;
            }

            liveSyncBusy = true;

            try {
                const response = await fetch(liveSyncUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();

                if (!payload.version) {
                    return;
                }

                if (currentVersion === null) {
                    currentVersion = payload.version;

                    return;
                }

                if (payload.version !== currentVersion && !liveSyncDismissed) {
                    liveSync.hidden = false;
                }
            } catch (error) {
            } finally {
                liveSyncBusy = false;
            }
        };

        reloadButton?.addEventListener('click', () => window.location.reload());
        dismissButton?.addEventListener('click', () => {
            liveSyncDismissed = true;
            liveSync.hidden = true;
        });

        checkStorefrontVersion();
        window.setInterval(checkStorefrontVersion, 45000);
        window.addEventListener('focus', checkStorefrontVersion);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                checkStorefrontVersion();
            }
        });
    }

    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
            new window.bootstrap.Tooltip(element);
        });
    }
});
