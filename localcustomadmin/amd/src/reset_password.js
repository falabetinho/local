define(['core/modal_save_cancel', 
    'core/str', 
    'core/ajax',
    'core/notification'], 
    function(ModalSaveCancel, Str, Ajax, Notification){
    'use strict';

    /**
     * Abre o modal de reset de senha
     * @param {number} userId ID do usuário
     */
    const open = function(userId) {
        // Log para debug
        console.log('[reset_password.open] Called with userId:', userId);
        
        // Obtém as strings do modal primeiro
        Promise.all([
            Str.get_string('resetpassword', 'local_localcustomadmin'),
            getBody()
        ]).then(function(results) {
            const title = results[0];
            const body = results[1];

            // Cria o modal usando modal_save_cancel
            const modal = new ModalSaveCancel({
                title: title,
                body: body,
                buttons: {
                    save: Str.get_string('save', 'core'),
                    cancel: Str.get_string('cancel', 'core')
                }
            });
            
            // Armazena o userId no modal para uso posterior
            modal.userId = userId;
            
            // Configura o botão Salvar
            modal.getRoot().on(modal.events.save, function(e) {
                e.preventDefault();
                handleSubmit(modal);
            });
            
            // Mostra o modal
            modal.show();
            
            return modal;
        }).catch(function(error) {
            console.error('[reset_password.open] Error:', error);
            Notification.addNotification({
                message: 'Erro ao abrir o modal de reset de senha',
                type: 'danger'
            });
        });
    };

    /**
     * Retorna o corpo do modal (formulário)
     * @return {Promise}
     */
    const getBody = function() {
        return Promise.all([
            Str.get_string('newpassword', 'local_localcustomadmin'),
            Str.get_string('confirmpassword', 'local_localcustomadmin'),
            Str.get_string('passwordmustmatch', 'local_localcustomadmin'),
            Str.get_string('passwordempty', 'local_localcustomadmin')
        ]).then(function(strings) {
            const newPasswordLabel = strings[0];
            const confirmPasswordLabel = strings[1];
            const matchErrorMsg = strings[2];
            const emptyErrorMsg = strings[3];
            
            const bodyHtml = `
                <div class="reset-password-form">
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">${newPasswordLabel}</label>
                        <input type="password" class="form-control" id="newPassword" 
                               placeholder="Digite a nova senha" required>
                        <small class="form-text text-muted">
                            ${emptyErrorMsg}
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">${confirmPasswordLabel}</label>
                        <input type="password" class="form-control" id="confirmPassword" 
                               placeholder="Confirme a nova senha" required>
                    </div>
                    <div id="passwordError" class="alert alert-danger d-none" role="alert">
                        ${matchErrorMsg}
                    </div>
                </div>
            `;
            
            return bodyHtml;
        });
    };

    /**
     * Valida e envia o formulário
     * @param {Object} modal Modal do Moodle
     */
    const handleSubmit = function(modal) {
        console.log('[reset_password.handleSubmit] Starting submit');
        
        const root = modal.getRoot();
        const newPassword = root[0].querySelector('#newPassword').value;
        const confirmPassword = root[0].querySelector('#confirmPassword').value;
        const errorDiv = root[0].querySelector('#passwordError');
        
        // Valida se as senhas estão vazias
        if (!newPassword.trim() || !newPassword.trim()) {
            errorDiv.classList.remove('d-none');
            return;
        }
        
        // Valida se as senhas conferem
        if (newPassword !== confirmPassword) {
            errorDiv.classList.remove('d-none');
            return;
        }
        
        // Remove mensagem de erro se houver
        errorDiv.classList.add('d-none');
        
        console.log('[reset_password.handleSubmit] Passwords validated, sending to server');
        
        // Chama o webservice
        Ajax.call([{
            methodname: 'local_localcustomadmin_reset_password',
            args: {
                userid: modal.userId,
                password: newPassword
            },
            done: function(response) {
                console.log('[reset_password.handleSubmit] Success:', response);
                
                Notification.addNotification({
                    message: 'Senha alterada com sucesso!',
                    type: 'success'
                });
                
                // Aguarda um pouco antes de fechar para a notificação ser vista
                setTimeout(function() {
                    modal.destroy();
                }, 1500);
            },
            fail: function(error) {
                console.error('[reset_password.handleSubmit] Error:', error);
                
                let errorMessage = 'Erro ao alterar a senha. Tente novamente.';
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