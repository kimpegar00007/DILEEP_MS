window.DILPLocation = window.DILPLocation || {};

(function (DILPLocation) {
    function getElement(selector) {
        return selector ? document.querySelector(selector) : null;
    }

    function getSavedValue(selector) {
        const el = getElement(selector);
        return el ? el.value.trim() : '';
    }

    function createStatusElement(container, message, statusType) {
        if (!container) return null;

        let statusEl = container.querySelector('.location-status-message');
        if (!statusEl) {
            statusEl = document.createElement('small');
            statusEl.className = 'location-status-message d-block mt-1';
            container.appendChild(statusEl);
        }

        const statusClasses = ['text-info', 'text-success', 'text-warning', 'text-danger'];
        statusEl.classList.remove(...statusClasses);
        statusEl.classList.add(statusType || 'text-info');
        statusEl.innerHTML = message;
        return statusEl;
    }

    function clearStatus(container) {
        const statusEl = container ? container.querySelector('.location-status-message') : null;
        if (statusEl) {
            statusEl.remove();
        }
    }

    async function queryGeocode(province, municipality, barangay) {
        if (!municipality) {
            return { success: false, message: 'Municipality is required for geocoding.' };
        }

        const params = new URLSearchParams({
            municipality: municipality.trim()
        });

        if (province) {
            params.append('province', province.trim());
        }

        if (barangay) {
            params.append('barangay', barangay.trim());
        }

        try {
            const response = await fetch(`api/geocode.php?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Geocode API error: ${response.status} ${errorText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Location autofill API error:', error);
            return { success: false, message: 'Geocoding service error. Please enter coordinates manually.' };
        }
    }

    async function fetchJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            throw new Error('Failed to fetch ' + url);
        }
        return response.json();
    }

    DILPLocation.initLocationAutoFill = function (options) {
        const provinceSelect = getElement(options.provinceSelect);
        const municipalitySelect = getElement(options.municipalitySelect);
        const barangaySelect = getElement(options.barangaySelect);
        const provinceValue = getSavedValue(options.provinceValueInput);
        const municipalityValue = getSavedValue(options.municipalityValueInput);
        const barangayValue = getSavedValue(options.barangayValueInput);
        const geoButton = getElement(options.geocodeButton);
        const latInput = getElement(options.latitudeInput);
        const lngInput = getElement(options.longitudeInput);
        let statusContainer = getElement(options.statusContainer) || (geoButton ? geoButton.parentElement : null);
        if (!statusContainer && latInput) {
            statusContainer = latInput.parentElement;
        }

        if (!provinceSelect || !municipalitySelect || !latInput || !lngInput) {
            return;
        }

        async function loadProvinces() {
            try {
                const result = await fetchJson('api/get-locations.php?action=provinces');
                if (!result.success) {
                    throw new Error(result.message || 'Unable to load provinces');
                }

                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                provinceSelect.disabled = false;

                result.data.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.name;
                    option.textContent = province.name;
                    option.dataset.code = province.code;
                    if (provinceValue && province.name === provinceValue) {
                        option.selected = true;
                    }
                    provinceSelect.appendChild(option);
                });

                if (provinceValue && provinceSelect.value) {
                    const provinceCode = provinceSelect.selectedOptions[0].dataset.code;
                    await loadMunicipalities(provinceCode, municipalityValue);
                }
            } catch (error) {
                console.error('Error loading provinces:', error);
                createStatusElement(statusContainer, '<i class="bi bi-exclamation-triangle"></i> Failed to load provinces.', 'text-warning');
            }
        }

        async function loadMunicipalities(provinceCode, savedMunicipality) {
            municipalitySelect.innerHTML = '<option value="">Loading municipalities...</option>';
            municipalitySelect.disabled = true;
            if (barangaySelect) {
                barangaySelect.innerHTML = '<option value="">Select Municipality first</option>';
                barangaySelect.disabled = true;
            }

            try {
                const result = await fetchJson(`api/get-locations.php?action=cities&province_code=${provinceCode}`);
                if (!result.success) {
                    throw new Error(result.message || 'Unable to load municipalities');
                }

                municipalitySelect.innerHTML = '<option value="">Select Municipality/City</option>';
                municipalitySelect.disabled = false;
                result.data.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = city.name;
                    option.dataset.code = city.code;
                    if (savedMunicipality && city.name === savedMunicipality) {
                        option.selected = true;
                    }
                    municipalitySelect.appendChild(option);
                });

                if (savedMunicipality && municipalitySelect.value && barangaySelect) {
                    const cityCode = municipalitySelect.selectedOptions[0].dataset.code;
                    await loadBarangays(cityCode, barangayValue);
                }
            } catch (error) {
                console.error('Error loading municipalities:', error);
                createStatusElement(statusContainer, '<i class="bi bi-exclamation-triangle"></i> Failed to load municipalities.', 'text-warning');
            }
        }

        async function loadBarangays(cityCode, savedBarangay) {
            if (!barangaySelect) {
                return;
            }

            barangaySelect.innerHTML = '<option value="">Loading barangays...</option>';
            barangaySelect.disabled = true;

            try {
                const result = await fetchJson(`api/get-locations.php?action=barangays&city_code=${cityCode}`);
                if (!result.success) {
                    throw new Error(result.message || 'Unable to load barangays');
                }

                barangaySelect.innerHTML = '<option value="">Select Barangay (optional)</option>';
                barangaySelect.disabled = false;
                result.data.forEach(barangay => {
                    const option = document.createElement('option');
                    option.value = barangay.name;
                    option.textContent = barangay.name;
                    if (savedBarangay && barangay.name === savedBarangay) {
                        option.selected = true;
                    }
                    barangaySelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading barangays:', error);
                createStatusElement(statusContainer, '<i class="bi bi-exclamation-triangle"></i> Failed to load barangays.', 'text-warning');
            }
        }

        async function geocodeLocation() {
            const province = provinceSelect.value;
            const municipality = municipalitySelect.value;
            const barangay = barangaySelect ? barangaySelect.value : '';

            if (!municipality) {
                createStatusElement(statusContainer, '<i class="bi bi-exclamation-triangle"></i> Select a municipality first.', 'text-warning');
                return;
            }

            const buttonContainer = geoButton ? geoButton.parentElement : statusContainer;
            if (geoButton) {
                geoButton.disabled = true;
                geoButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Fetching...';
            }
            createStatusElement(statusContainer, '<i class="bi bi-hourglass-split"></i> Fetching coordinates...', 'text-info');

            try {
                const result = await queryGeocode(province, municipality, barangay);
                if (result.success) {
                    latInput.value = result.latitude.toFixed(8);
                    lngInput.value = result.longitude.toFixed(8);
                    createStatusElement(statusContainer, `<i class="bi bi-check-circle"></i> Coordinates auto-filled for ${result.display_name}.`, 'text-success');
                } else {
                    createStatusElement(statusContainer, `<i class="bi bi-exclamation-triangle"></i> ${result.message}`, 'text-warning');
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                createStatusElement(statusContainer, '<i class="bi bi-x-circle"></i> Geocoding failed.', 'text-danger');
            } finally {
                if (geoButton) {
                    geoButton.disabled = false;
                    geoButton.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Auto-fill Coordinates';
                }
            }
        }

        provinceSelect.addEventListener('change', async function () {
            const provinceCode = this.selectedOptions[0]?.dataset.code;
            if (provinceCode) {
                await loadMunicipalities(provinceCode);
            } else {
                municipalitySelect.innerHTML = '<option value="">Select Province first</option>';
                municipalitySelect.disabled = true;
                if (barangaySelect) {
                    barangaySelect.innerHTML = '<option value="">Select Municipality first</option>';
                    barangaySelect.disabled = true;
                }
            }
            clearStatus(statusContainer);
        });

        municipalitySelect.addEventListener('change', async function () {
            const cityCode = this.selectedOptions[0]?.dataset.code;
            if (cityCode && barangaySelect) {
                await loadBarangays(cityCode);
            }
            clearStatus(statusContainer);
            if (!geoButton && !barangaySelect) {
                await geocodeLocation();
            }
        });

        if (barangaySelect) {
            barangaySelect.addEventListener('change', function () {
                clearStatus(statusContainer);
                geocodeLocation();
            });
        }

        if (geoButton) {
            geoButton.addEventListener('click', function () {
                geocodeLocation();
            });
        }

        loadProvinces();
    };
})(window.DILPLocation);
