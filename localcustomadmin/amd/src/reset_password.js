define(['jquery', 'core/modal_factory', 'core/str', 'core/ajax', 'core/notification'], 
    function($, ModalFactory, Str, Ajax, Notification){
    'use strict';

    /**
     * Abre o modal de reset de senha
     * @param {number} userId ID do usuário
     */
    const open = function(userId) {
        console.log('[reset_password.open] Called with userId:', userId);
        
        // Carrega as strings
        const promises = [
            Str.get_string('resetpassword', 'local_localcustomadmin'),
            Str.get_string('newpassword', 'local_localcustomadmin'),
            Str.get_string('confirmpassword', 'local_localcustomadmin'),
            Str.get_string('passwordmustmatch', 'local_localcustomadmin'),
            Str.get_string('passwordempty', 'local_localcustomadmin'),
            Str.get_string('passwordresetsuccess', 'local_localcustomadmin'),
            Str.get_string('passwordresetfailed', 'local_localcustomadmin')
        ];
        
        $.when.apply($, promises).done(function(title, newPasswordLabel, confirmPasswordLabel, matchErrorMsg, emptyErrorMsg, successMsg, failureMsg) {
            console.log('[reset_password.open] Strings loaded');
            
            // Cria o HTML do modal
            const bodyHtml = $(`
                <div class="reset-password-form">
                    <div class="form-group mb-3">
                        <label for="newPassword" class="form-label">${newPasswordLabel}</label>
                        <input type="password" class="form-control" id="newPassword" required>
                        <small class="form-text text-muted">${emptyErrorMsg}</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="confirmPassword" class="form-label">${confirmPasswordLabel}</label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                    </div>
                    <div id="passwordError" class="alert alert-danger d-none" role="alert">
                        ${matchErrorMsg}
                    </div>
                </div>
            `);
            
            // Armazena as mensagens para uso posterior
            window.resetPasswordStrings = {
                success: successMsg,
                failure: failureMsg
            };
            
            // Cria o modal
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: bodyHtml
            }).done(function(modal) {
                console.log('[reset_password.open] Modal created successfully');
                
                // Armazena o userId no modal
                modal.userId = userId;
                
                // Configura evento no botão Salvar
                const root = modal.getRoot();
                root.on('click', '[data-action="save"]', function(e) {
                    e.preventDefault();
                    console.log('[reset_password.open] Save button clicked');
                    handleSubmit(modal);
                });
                
                // Mostra o modal
                modal.show();
            }).fail(function(error) {
                console.error('[reset_password.open] Modal creation failed:', error);
                Notification.addNotification({
                    message: 'Erro ao criar o modal',
                    type: 'danger'
                });
            });
        }).fail(function(error) {
            console.error('[reset_password.open] Error loading strings:', error);
            Notification.addNotification({
                message: 'Erro ao carregar strings',
                type: 'danger'
            });
        });
    };

    /**
     * Valida e envia o formulário
     * @param {Object} modal Modal do Moodle
     */
    const handleSubmit = function(modal) {
        console.log('[reset_password.handleSubmit] Starting submit');
        
        const root = modal.getRoot();
        const newPassword = root.find('#newPassword').val().trim();
        const confirmPassword = root.find('#confirmPassword').val().trim();
        const errorDiv = root.find('#passwordError');
        
        // Valida senhas vazias
        if (!newPassword || !confirmPassword) {
            errorDiv.removeClass('d-none');
            console.log('[reset_password.handleSubmit] Passwords are empty');
            return;
        }
        
        // Valida se as senhas conferem
        if (newPassword !== confirmPassword) {
            errorDiv.removeClass('d-none');
            console.log('[reset_password.handleSubmit] Passwords do not match');
            return;
        }
        
        // Remove mensagem de erro
        errorDiv.addClass('d-none');
        
        console.log('[reset_password.handleSubmit] Sending to server - userId:', modal.userId);
        
        // Chama o webservice via POST
        Ajax.call([{
            methodname: 'local_localcustomadmin_reset_password',
            method: 'post',
            args: {
                userid: modal.userId,
                password: newPassword
            },
            done: function(response) {
                console.log('[reset_password.handleSubmit] Success:', response);
                
                // Usa a string carregada do idioma
                let successMessage = 'Senha alterada com sucesso!';
                if (window.resetPasswordStrings && window.resetPasswordStrings.success) {
                    successMessage = window.resetPasswordStrings.success;
                }
                
                Notification.addNotification({
                    message: successMessage,
                    type: 'success'
                });
                setTimeout(function() {
                    modal.destroy();
                }, 1500);
            },
            fail: function(error) {
                console.error('[reset_password.handleSubmit] Error:', error);
                
                // Usa a string carregada do idioma
                let errorMessage = 'Erro ao alterar a senha. Tente novamente.';
                if (window.resetPasswordStrings && window.resetPasswordStrings.failure) {
                    errorMessage = window.resetPasswordStrings.failure;
                }
                if (error && error.error) {
                    errorMessage = error.error;
                }
                
                Notification.addNotification({
                    message: errorMessage,
                    type: 'danger'
                });
            }
        }]);
    };

    return {
        open: open
    };
});