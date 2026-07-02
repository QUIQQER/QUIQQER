define('controls/users/auth/WebAuthnUtils', [], function () {
    'use strict';

    const base64UrlToBuffer = function (value) {
        value = value.replace(/-/g, '+').replace(/_/g, '/');

        const padding = value.length % 4;
        if (padding) {
            value += '='.repeat(4 - padding);
        }

        const binary = window.atob(value);
        const buffer = new ArrayBuffer(binary.length);
        const bytes = new Uint8Array(buffer);

        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }

        return buffer;
    };

    const bufferToBase64Url = function (buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';

        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }

        return window.btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=/g, '');
    };

    const prepareCreateOptions = function (options) {
        const publicKey = options.publicKey;

        publicKey.challenge = base64UrlToBuffer(publicKey.challenge);
        publicKey.user.id = base64UrlToBuffer(publicKey.user.id);

        if (publicKey.excludeCredentials) {
            publicKey.excludeCredentials.forEach((credential) => {
                credential.id = base64UrlToBuffer(credential.id);
            });
        }

        return options;
    };

    const prepareGetOptions = function (options) {
        const publicKey = options.publicKey;

        publicKey.challenge = base64UrlToBuffer(publicKey.challenge);

        if (publicKey.allowCredentials) {
            publicKey.allowCredentials.forEach((credential) => {
                credential.id = base64UrlToBuffer(credential.id);
            });
        }

        return options;
    };

    const getErrorLocaleKey = function (err) {
        if (err && err.name === 'InvalidStateError') {
            return 'quiqqer.webauthn.error.already_registered_on_device';
        }

        if (err && err.name === 'NotAllowedError') {
            return 'quiqqer.webauthn.error.cancelled';
        }

        return 'quiqqer.webauthn.error.failed';
    };

    return {
        isSupported: function () {
            return !!window.PublicKeyCredential && !!navigator.credentials;
        },

        prepareCreateOptions: prepareCreateOptions,
        prepareGetOptions: prepareGetOptions,
        getErrorLocaleKey: getErrorLocaleKey,

        serializeAttestation: function (credential) {
            return {
                id: credential.id,
                rawId: bufferToBase64Url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                    attestationObject: bufferToBase64Url(credential.response.attestationObject)
                },
                transports: typeof credential.response.getTransports === 'function'
                    ? credential.response.getTransports()
                    : []
            };
        },

        serializeAssertion: function (credential) {
            return {
                id: credential.id,
                rawId: bufferToBase64Url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                    authenticatorData: bufferToBase64Url(credential.response.authenticatorData),
                    signature: bufferToBase64Url(credential.response.signature),
                    userHandle: credential.response.userHandle
                        ? bufferToBase64Url(credential.response.userHandle)
                        : ''
                }
            };
        }
    };
});
