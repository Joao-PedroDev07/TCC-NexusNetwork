document.addEventListener('DOMContentLoaded', function () {
    // Seleciona todas as divs .rating
    const ratings = document.querySelectorAll('.rating');
  
    ratings.forEach(rating => {
      // Para cada estrela dentro da div
      rating.querySelectorAll('i').forEach(star => {
        star.addEventListener('click', () => {
          const presId = rating.getAttribute('data-pres-id');
          const nota = star.getAttribute('data-star');
          if (presId && nota) {
            // Redireciona para avaliacao.php com parâmetros
            window.location.href = `avaliacao.php?pres_codigo=${presId}&avl_nota=${nota}`;
          }
        });
      });
    });
  });
  