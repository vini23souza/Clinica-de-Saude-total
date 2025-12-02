// app.js - Funcionalidades JavaScript para a Clínica Profissional

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Inicializar todas as funcionalidades
    initMasks();
    initFormValidations();
    initAnimations();
    initNotifications();
}

// Sistema de Máscaras para formulários
function initMasks() {
    // Máscara para CPF
    const cpfInputs = document.querySelectorAll('input[name="cpf"]');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            e.target.value = value;
        });
    });

    // Máscara para Telefone
    const telInputs = document.querySelectorAll('input[name="telefone"], input[type="tel"]');
    telInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    });

    // Máscara para CRM
    const crmInputs = document.querySelectorAll('input[name="crm"]');
    crmInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });
    });
}

// Sistema de Validação de Formulários
function initFormValidations() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('Por favor, corrija os erros no formulário.', 'error');
            }
        });
    });

    // Validação em tempo real
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    
    // Remover estilos de erro anteriores
    field.classList.remove('error-field');
    
    // Verificar se está vazio
    if (!value) {
        isValid = false;
        field.classList.add('error-field');
        return isValid;
    }
    
    // Validações específicas por tipo
    switch(field.type) {
        case 'email':
            if (!isValidEmail(value)) {
                isValid = false;
                field.classList.add('error-field');
            }
            break;
            
        case 'tel':
            if (!isValidPhone(value)) {
                isValid = false;
                field.classList.add('error-field');
            }
            break;
            
        case 'text':
            if (field.name === 'cpf' && !isValidCPF(value)) {
                isValid = false;
                field.classList.add('error-field');
            }
            break;
    }
    
    return isValid;
}

// Funções de validação
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^\(\d{2}\) \d{4,5}-\d{4}$/;
    return phoneRegex.test(phone);
}

function isValidCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    return cpf.length === 11;
}

// Sistema de Animações
function initAnimations() {
    // Animação de entrada para cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Sistema de Notificações
function initNotifications() {
    // Auto-remover mensagens após 5 segundos
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'all 0.5s ease';
            message.style.opacity = '0';
            message.style.height = '0';
            message.style.margin = '0';
            message.style.padding = '0';
            
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `message ${type}`;
    notification.textContent = message;
    
    // Adicionar ao topo da página
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(notification, container.firstChild);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Funções utilitárias
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR');
}

// Exportar funções para uso global
window.ClinicaApp = {
    showNotification,
    formatDate,
    formatDateTime,
    validateForm
};

// Adicionar CSS para campos com erro
const errorStyles = document.createElement('style');
errorStyles.textContent = `
    .error-field {
        border-color: #f44336 !important;
        box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2) !important;
    }
`;
document.head.appendChild(errorStyles);