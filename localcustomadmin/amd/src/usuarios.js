define(['local_localcustomadmin/reset_password'], function(ResetPassword) {
    'use strict';
    
    const init = function() {
        console.log('[usuarios.init] Inicializado.');
        
        // Busca todos os botões de reset de senha
        const resetButtons = document.querySelectorAll('button[data-userid]');
        console.log('[usuarios.init] Encontrados', resetButtons.length, 'botões de reset');
        
        // Adiciona event listener para cada botão
        resetButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const userId = this.getAttribute('data-userid');
                console.log('[usuarios.init] Button clicked, userId:', userId);
                
                if (userId) {
                    ResetPassword.open(userId);
                }
            });
        });
    };  
    
    return {
        init: init
    };
});


