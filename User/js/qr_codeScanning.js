// Global initialization function
window.initializeQRScanner = function() {
    // Ensure DOM is fully loaded
    if (document.readyState !== 'loading') {
        initializeScanner();
    } else {
        document.addEventListener('DOMContentLoaded', initializeScanner);
    }

    function initializeScanner() {
        // Comprehensive library check and loading
        function checkAndInitializeQRScanner() {
            // Specific library globals to check
            const libraryGlobals = [
                'Html5Qrcode',
                'html5Qrcode',
                'Html5QrcodeScanner',
                'html5QrcodeScanner'
            ];

            // Get status elements
            const libraryStatus = document.querySelector('.scanning-detail-item.library-status');
            const cameraStatus = document.querySelector('.scanning-detail-item.camera-status');
            const detectionStatus = document.querySelector('.scanning-detail-item.detection-status');

            // Update status function
            function updateStatus(element, message, isError = false) {
                if (element) {
                    element.textContent = message;
                    element.classList.toggle('error', isError);
                }
                console.log(message);
            }

            // Find the first available global
            const availableLibrary = libraryGlobals.find(global => typeof window[global] !== 'undefined');

            // If no library is found
            if (!availableLibrary) {
                updateStatus(libraryStatus, 'Library: Not Loaded', true);
                updateStatus(cameraStatus, 'Camera: Unavailable', true);
                updateStatus(detectionStatus, 'Detection: Library Error', true);
                
                console.error('QR Code scanning library is not available');
                return null;
            }

            // Determine the correct constructor
            const Html5QrcodeConstructor = window.Html5Qrcode || window.html5Qrcode;

            // Validate the constructor
            if (typeof Html5QrcodeConstructor !== 'function') {
                updateStatus(libraryStatus, 'Library: Invalid Constructor', true);
                updateStatus(cameraStatus, 'Camera: Unavailable', true);
                updateStatus(detectionStatus, 'Detection: Library Error', true);
                
                console.error('QR Code library constructor is not a function');
                return null;
            }

            // Library is available, proceed with initialization
            updateStatus(libraryStatus, 'Library: Loaded', false);
            return Html5QrcodeConstructor;
        }

        // Attempt to initialize QR scanner
        function initializeQRScanner(Html5QrcodeConstructor) {
            const qrScannerContainer = document.getElementById('qr-scanner-container');
            const videoElement = document.getElementById('camera');
            const libraryStatus = document.querySelector('.scanning-detail-item.library-status');
            const cameraStatus = document.querySelector('.scanning-detail-item.camera-status');
            const detectionStatus = document.querySelector('.scanning-detail-item.detection-status');

            // Scanning state management
            let isScanning = false;
            let scanningAttempts = 0;
            const MAX_SCANNING_ATTEMPTS = 5;

            // Update status function
            function updateStatus(element, message, isError = false) {
                if (element) {
                    element.textContent = message;
                    element.classList.toggle('error', isError);
                }
                console.log(message);
            }

            // Ensure we have a valid container
            if (!qrScannerContainer) {
                updateStatus(detectionStatus, 'Detection: Scanner Container Missing', true);
                return;
            }

            // Create HTML5 QR Code scanner
            const html5QrCode = new Html5QrcodeConstructor(qrScannerContainer.id);

            // Scan success handler
            const onScanSuccess = (decodedText, decodedResult) => {
                // Prevent multiple simultaneous validations
                if (isScanning) return;

                isScanning = true;
                updateStatus(detectionStatus, 'Detection: QR Code Scanned', false);
                
                // Stop scanning
                html5QrCode.stop().then(() => {
                    // Validate the scanned QR code
                    validateQRCode(decodedText);
                }).catch(err => {
                    console.error('Error stopping QR scanner:', err);
                    updateStatus(detectionStatus, 'Detection: Scan Stop Error', true);
                    isScanning = false;
                });
            };

            // Scan failure handler
            const onScanFailure = (error) => {
                // Increment scanning attempts
                scanningAttempts++;

                // Reduce error logging frequency
                if (scanningAttempts % 3 === 0) {
                    updateStatus(detectionStatus, 'Detection: Scanning Paused', true);
                    console.warn('Repeated scanning failures. Pausing detection.', error);
                }

                // Reset attempts if max is reached
                if (scanningAttempts >= MAX_SCANNING_ATTEMPTS) {
                    updateStatus(detectionStatus, 'Detection: Too Many Failures', true);
                    console.error('Maximum scanning attempts reached');
                    return;
                }
            };

            // Get available cameras
            Html5QrcodeConstructor.getCameras().then(devices => {
                if (devices && devices.length) {
                    const cameraId = devices[0].id;
                    updateStatus(cameraStatus, `Camera: ${devices[0].label}`, false);

                    // Start scanning with reduced frequency and more robust configuration
                    html5QrCode.start(
                        cameraId, 
                        {
                            fps: 5,     // Reduced frames per second
                            qrbox: {
                                width: 250,
                                height: 250
                            },
                            disableFlip: false,
                            aspectRatio: 1.778
                        }, 
                        onScanSuccess, 
                        onScanFailure
                    ).then(() => {
                        updateStatus(detectionStatus, 'Detection: Scanning Active', false);
                    }).catch(err => {
                        updateStatus(cameraStatus, 'Camera: Failed to Start', true);
                        console.error('Error starting QR scanner:', err);
                    });
                } else {
                    updateStatus(cameraStatus, 'Camera: No Cameras Found', true);
                }
            }).catch(err => {
                updateStatus(cameraStatus, 'Camera: Detection Failed', true);
                console.error('Camera detection error:', err);
            });
        }

        // Validate QR Code
        function validateQRCode(scannedCode) {
            const detectionStatus = document.querySelector('.scanning-detail-item.detection-status');

            function updateStatus(message, isError = false) {
                if (detectionStatus) {
                    detectionStatus.textContent = message;
                    detectionStatus.classList.toggle('error', isError);
                }
                console.log(message);
            }

            // Send scanned code to server for validation
            fetch('validate_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ qr_code: scannedCode })
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    // Successful validation
                    updateStatus('Detection: Room Access Granted', false);
                    // Optional: Redirect or show success message
                } else {
                    // Validation failed
                    updateStatus('Detection: Invalid QR Code', true);
                }
            })
            .catch(error => {
                updateStatus('Detection: Validation Error', true);
                console.error('QR Code validation error:', error);
            })
            .finally(() => {
                // Reset scanning state
                isScanning = false;
            });
        }

        // Main initialization
        const Html5QrcodeConstructor = checkAndInitializeQRScanner();
        if (Html5QrcodeConstructor) {
            initializeQRScanner(Html5QrcodeConstructor);
        }
    }
}; 