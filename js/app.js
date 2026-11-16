
// Confirmo antes de enviar los formularios que llevan data-confirmar
let formsConfirmar = document.querySelectorAll('form[data-confirmar]');
for (let i = 0; i < formsConfirmar.length; i++) {
  formsConfirmar[i].addEventListener('submit', function (e) {
    if (!confirm(this.getAttribute('data-confirmar'))) {
      e.preventDefault();
    }
  });
}

// Validación de Bootstrap en los formularios con la clase "validar" y comprobación de que las dos contraseñas coinciden
let formsValidar = document.querySelectorAll('form.validar');
for (let j = 0; j < formsValidar.length; j++) {
  formsValidar[j].addEventListener('submit', function (e) {
    let pass = this.querySelector('.campo-password');
    let repetir = this.querySelector('.campo-repetir');
    if (pass && repetir) {
      if (pass.value != repetir.value) {
        repetir.setCustomValidity('No coinciden');
      } else {
        repetir.setCustomValidity('');
      }
    }
    if (!this.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    this.classList.add('was-validated');
  });
}

// Buscador de la tienda: oculta los productos que no coinciden
let buscador = document.getElementById('buscador');
if (buscador) {
  buscador.addEventListener('input', function () {
    let texto = buscador.value.toLowerCase();
    let tarjetas = document.querySelectorAll('.producto');
    let visibles = 0;
    for (let k = 0; k < tarjetas.length; k++) {
      let nombre = tarjetas[k].getAttribute('data-nombre');
      if (nombre.indexOf(texto) != -1) {
        tarjetas[k].classList.remove('d-none');
        visibles++;
      } else {
        tarjetas[k].classList.add('d-none');
      }
    }
    let aviso = document.getElementById('sinResultados');
    if (aviso) {
      if (visibles == 0) { aviso.classList.remove('d-none'); } else { aviso.classList.add('d-none'); }
    }
  });
}

// Vista previa de la foto de perfil
let campoFoto = document.getElementById('campoFoto');
if (campoFoto) {
  campoFoto.addEventListener('change', function () {
    let vista = document.getElementById('vistaFoto');
    if (campoFoto.files.length > 0) {
      vista.src = URL.createObjectURL(campoFoto.files[0]);
      vista.classList.remove('d-none');
    } else {
      vista.classList.add('d-none');
    }
  });
}