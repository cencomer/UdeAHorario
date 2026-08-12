/**
 * pdf.js - Exportar horario como PDF
 * Usa window.print() con CSS de impresión optimizado.
 * Abre todos los accordions antes de imprimir para mostrar contenido completo.
 * 
 * @author Luis Cabezas - Inteligencia.com.co
 */

function exportPDF() {
  // Abrir todos los accordions para que se impriman completos
  document.querySelectorAll('.accordion').forEach(a => a.classList.add('open'));

  // Mostrar header de impresión
  const header = document.querySelector('.print-header');
  if (header) header.style.display = 'block';

  // Esperar un frame para que el DOM se actualice
  requestAnimationFrame(() => {
    window.print();

    // Restaurar estado después de imprimir
    setTimeout(() => {
      document.querySelectorAll('.accordion').forEach(a => a.classList.remove('open'));
      if (header) header.style.display = 'none';
    }, 500);
  });
}
