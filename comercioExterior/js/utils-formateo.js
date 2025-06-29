
// utils-formateo.js - Funciones compartidas para formateo de números

// Función para formatear números con punto como separador de miles y coma como decimal
window.formatearNumero = function(numero) {
    if (isNaN(numero) || numero === null || numero === '' || numero === undefined) {
        return '0,00';
    }
    return parseFloat(numero).toLocaleString('es-ES', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
}

// Función para convertir el formato español al formato numérico
window.convertirANumero = function(valorFormateado) {
    if (!valorFormateado || valorFormateado === '' || valorFormateado === null || valorFormateado === undefined) {
        return 0;
    }
    
    // Convertir a string si no lo es
    let valorStr = valorFormateado.toString().trim();
    
    // Si ya es un número válido, devolverlo
    if (!isNaN(valorStr) && !valorStr.includes('.') && !valorStr.includes(',')) {
        return parseFloat(valorStr) || 0;
    }
    
    // Remover puntos (separadores de miles) y reemplazar coma por punto
    let numero = valorStr
        .replace(/\./g, '')    // Remover puntos (separadores de miles)
        .replace(',', '.');    // Reemplazar coma por punto decimal
        
    return parseFloat(numero) || 0;
}

// Función para formatear cuando el usuario termina de escribir
window.formatearInput = function(input) {
    if(!input || !input.value || input.value === '' || input.value === '0') {
        return;
    }
    let valorNumerico = window.convertirANumero(input.value);
    input.value = window.formatearNumero(valorNumerico);
}

// Función para limpiar input al hacer click
window.limpiarInput = function(input) {
    input.value = '';
}

// Función sacarParseo compatible con el formato anterior
window.sacarParseo = function(string, isNumber = false) {
    if (!string || string === '') return 0;
    return window.convertirANumero(string);
}

console.log('Utils de formateo cargadas correctamente');