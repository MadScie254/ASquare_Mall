/**
 * A Square Mall - API Handler
 * Handles M-Pesa, SMS, and Contact Form submissions
 */
class SquareMallAPI {
    constructor() {
        this.baseURL = window.location.origin;
        this.endpoints = {
            contact: '/api/contact.php',
            sms: '/api/sms.php',
            mpesa: '/api/mpesa.php'
        };
    }
    /**
     * Generic API request handler
     */
    async makeRequest(endpoint, data, method = 'POST') {
        try {
            const response = await fetch(this.baseURL + endpoint, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    /**
     * Contact Form Submission
     */
    async submitContactForm(formData) {
        try {
            const result = await this.makeRequest(this.endpoints.contact, {
                firstName: formData.firstName,
                lastName: formData.lastName,
                email: formData.email,
                phone: formData.phone,
                subject: formData.subject,
                message: formData.message,
                timestamp: new Date().toISOString()
            });
            // Simulate email notification
            console.log('📧 Email sent to:', formData.email);
            console.log('📧 Admin notification sent to: admin@asquaremall.co.ke');
            return result;
        } catch (error) {
            throw new Error('Failed to submit contact form: ' + error.message);
        }
    }
    /**
     * M-Pesa STK Push Simulation
     */
    async initiateMpesaPayment(phoneNumber, amount, description = 'A Square Mall Payment') {
        try {
            // Validate phone number (Kenyan format)
            const cleanPhone = phoneNumber.replace(/\D/g, '');
            if (!cleanPhone.match(/^(254|0)[7][0-9]{8}$/)) {
                throw new Error('Invalid phone number format. Use 254XXXXXXXXX or 07XXXXXXXX');
            }
            const mpesaData = {
                phoneNumber: cleanPhone,
                amount: amount,
                description: description,
                merchantRequestID: this.generateTransactionID(),
                checkoutRequestID: this.generateTransactionID(),
                timestamp: new Date().toISOString()
            };
            const result = await this.makeRequest(this.endpoints.mpesa, mpesaData);
            // Simulate STK Push
            console.log('📱 M-Pesa STK Push sent to:', phoneNumber);
            console.log('💰 Amount: KSh', amount);
            console.log('🆔 Transaction ID:', mpesaData.merchantRequestID);
            return {
                success: true,
                message: 'STK Push sent to your phone',
                transactionID: mpesaData.merchantRequestID,
                ...result
            };
        } catch (error) {
            throw new Error('M-Pesa payment failed: ' + error.message);
        }
    }
    /**
     * SMS Notification Service
     */
    async sendSMS(phoneNumber, message, type = 'notification') {
        try {
            const smsData = {
                phoneNumber: phoneNumber,
                message: message,
                type: type,
                timestamp: new Date().toISOString(),
                messageID: this.generateTransactionID()
            };
            const result = await this.makeRequest(this.endpoints.sms, smsData);
            console.log('📨 SMS sent to:', phoneNumber);
            console.log('📄 Message:', message);
            return {
                success: true,
                message: 'SMS sent successfully',
                messageID: smsData.messageID,
                ...result
            };
        } catch (error) {
            throw new Error('SMS sending failed: ' + error.message);
        }
    }
    /**
     * Newsletter Subscription
     */
    async subscribeNewsletter(email, name = '') {
        try {
            const subscriptionData = {
                email: email,
                name: name,
                source: 'website',
                timestamp: new Date().toISOString()
            };
            // Simulate Mailchimp API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            console.log('📬 Newsletter subscription:', email);
            return {
                success: true,
                message: 'Successfully subscribed to newsletter'
            };
        } catch (error) {
            throw new Error('Newsletter subscription failed: ' + error.message);
        }
    }
    /**
     * Leasing Application Submission
     */
    async submitLeasingApplication(applicationData) {
        try {
            const submission = {
                ...applicationData,
                applicationID: this.generateTransactionID(),
                status: 'pending',
                timestamp: new Date().toISOString()
            };
            // Simulate form submission
            await new Promise(resolve => setTimeout(resolve, 1500));
            console.log('🏢 Leasing application submitted:', submission.applicationID);
            console.log('📧 Email sent to:', applicationData.email);
            // Send confirmation SMS
            if (applicationData.phone) {
                await this.sendSMS(
                    applicationData.phone,
                    `Thank you for your leasing inquiry at A Square Mall. Application ID: ${submission.applicationID}. We'll contact you within 24 hours.`,
                    'confirmation'
                );
            }
            return {
                success: true,
                message: 'Application submitted successfully',
                applicationID: submission.applicationID
            };
        } catch (error) {
            throw new Error('Leasing application failed: ' + error.message);
        }
    }
    /**
     * Event Registration
     */
    async registerForEvent(eventID, userData) {
        try {
            const registration = {
                eventID: eventID,
                ...userData,
                registrationID: this.generateTransactionID(),
                timestamp: new Date().toISOString()
            };
            await new Promise(resolve => setTimeout(resolve, 800));
            console.log('🎟️ Event registration:', registration.registrationID);
            return {
                success: true,
                message: 'Event registration successful',
                registrationID: registration.registrationID
            };
        } catch (error) {
            throw new Error('Event registration failed: ' + error.message);
        }
    }
    /**
     * Store Inquiry
     */
    async submitStoreInquiry(storeID, inquiryData) {
        try {
            const inquiry = {
                storeID: storeID,
                ...inquiryData,
                inquiryID: this.generateTransactionID(),
                timestamp: new Date().toISOString()
            };
            await new Promise(resolve => setTimeout(resolve, 600));
            console.log('🏪 Store inquiry submitted:', inquiry.inquiryID);
            return {
                success: true,
                message: 'Inquiry sent to store',
                inquiryID: inquiry.inquiryID
            };
        } catch (error) {
            throw new Error('Store inquiry failed: ' + error.message);
        }
    }
    /**
     * Utility Methods
     */
    generateTransactionID() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 10000);
        return `ASM${timestamp}${random}`;
    }
    formatPhoneNumber(phone) {
        // Convert to Kenyan format
        let cleanPhone = phone.replace(/\D/g, '');
        
        if (cleanPhone.startsWith('0')) {
            cleanPhone = '254' + cleanPhone.substring(1);
        } else if (!cleanPhone.startsWith('254')) {
            cleanPhone = '254' + cleanPhone;
        }
        
        return cleanPhone;
    }
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    validateKenyanPhone(phone) {
        const cleanPhone = phone.replace(/\D/g, '');
        return /^(254|0)[7][0-9]{8}$/.test(cleanPhone);
    }
    /**
     * Error Handling
     */
    handleError(error, context = '') {
        console.error(`API Error${context ? ' (' + context + ')' : ''}:`, error);
        
        // Show user-friendly error messages
        const userMessage = this.getUserFriendlyError(error.message);
        
        // You could integrate with a notification system here
        return {
            success: false,
            error: userMessage,
            details: error.message
        };
    }
    getUserFriendlyError(errorMessage) {
        const errorMap = {
            'network': 'Network connection error. Please check your internet connection.',
            'timeout': 'Request timed out. Please try again.',
            'validation': 'Please check your input and try again.',
            'payment': 'Payment processing error. Please try again.',
            'sms': 'SMS sending failed. Please verify your phone number.',
            'default': 'Something went wrong. Please try again later.'
        };
        for (const [key, message] of Object.entries(errorMap)) {
            if (errorMessage.toLowerCase().includes(key)) {
                return message;
            }
        }
        return errorMap.default;
    }
}
// Initialize API instance
const SquareMallAPIInstance = new SquareMallAPI();
// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SquareMallAPI;
}
// Global helper functions for easy access
window.submitContactForm = (formData) => SquareMallAPIInstance.submitContactForm(formData);
window.initiateMpesaPayment = (phone, amount, desc) => SquareMallAPIInstance.initiateMpesaPayment(phone, amount, desc);
window.sendSMS = (phone, message, type) => SquareMallAPIInstance.sendSMS(phone, message, type);
window.subscribeNewsletter = (email, name) => SquareMallAPIInstance.subscribeNewsletter(email, name);
window.submitLeasingApplication = (data) => SquareMallAPIInstance.submitLeasingApplication(data);
console.log('🚀 A Square Mall API initialized successfully');