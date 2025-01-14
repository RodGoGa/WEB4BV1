document.addEventListener('DOMContentLoaded', function () {
    const skillsContainer = document.getElementById('skills-container');
    const addSkillBtn = document.getElementById('add-skill-btn');
    const skillsMessage = document.getElementById('skills-message');
    const submitBtn = document.getElementById('submit-btn');

    let skillCount = 0;

    // Función para crear un nuevo input
    function createSkillInput() {
        // Crear un contenedor para la habilidad
        const skillDiv = document.createElement('div');
        skillDiv.classList.add('skill-item');

        // Crear un input
        const skillInput = document.createElement('textarea');
        skillInput.type = 'text';
        skillInput.placeholder = 'Escribe una habilidad';
        skillInput.classList.add('dynamic-input');

        // Crear botón de eliminar
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.classList.add('remove-btn');
        removeBtn.textContent = 'X';

        // Agregar elementos al contenedor
        skillDiv.appendChild(skillInput);
        skillDiv.appendChild(removeBtn);
        skillsContainer.appendChild(skillDiv);

        // Incrementar contador y ocultar mensaje
        skillCount++;
        updateMessage();
        validateInputs();
        skillInput.focus();

        skillInput.addEventListener('input', function () {
            validateInputs();
            adjustTextareaSize(this);
        });

        adjustTextareaSize(skillInput);

        // Evento para eliminar el input
        removeBtn.addEventListener('click', function () {
            skillsContainer.removeChild(skillDiv);
            addSkillBtn.disabled = false; // Reactivar el botón al eliminar
            skillCount--;
            updateMessage();
            validateInputs();
        });
    }

    // Evento para ajustar el tamaño del textarea dinámicamente
    function adjustTextareaSize(textarea) {
        const skillItem = textarea.parentElement;
    
        // Reiniciar el tamaño antes de recalcular
        textarea.style.width = 'auto';
        textarea.style.height = '1.2em'; // Reinicia a una línea
        skillItem.style.width = 'auto';
    
        // Crear un span temporal para medir el ancho exacto del contenido
        const span = document.createElement('span');
        span.style.visibility = 'hidden';
        span.style.position = 'absolute';
        span.style.whiteSpace = 'pre-wrap'; // Igual que el textarea
        span.style.font = window.getComputedStyle(textarea).font;
        span.textContent = textarea.value || textarea.placeholder;
    
        document.body.appendChild(span);
    
        // Ancho dinámico
        const containerWidth = skillsContainer.offsetWidth - 20; // Límite del contenedor
        const contentWidth = span.offsetWidth + 50; // Contenido + padding
    
        const finalWidth = Math.min(contentWidth, containerWidth);
    
        // Ajustar el ancho del textarea y su contenedor skill-item
        skillItem.style.width = `${finalWidth}px`;
        textarea.style.width = `${finalWidth}px`;
    
        // Ajustar la altura dinámica al contenido visible
        textarea.style.height = `${textarea.scrollHeight}px`;
    
        document.body.removeChild(span);
    }

    // Función para validar todos los inputs
    function validateInputs() {
        const allInputs = document.querySelectorAll('#skills-container textarea');
        let allFilled = true;
    
        allInputs.forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('input-empty'); // Resalta si está vacío
                allFilled = false;
            } else {
                input.classList.remove('input-empty'); // Quita la clase si tiene contenido
            }
        });
    
        // Si algún input está vacío, desactiva el botón
        addSkillBtn.disabled = !allFilled;
    }

    // Mostrar/ocultar mensaje inicial
    function updateMessage() {
        if (skillCount > 0) {
            skillsMessage.classList.add('skills-hidden');
        } else {
            skillsMessage.classList.remove('skills-hidden');
        }
    }

    submitBtn.addEventListener('click', function (e) {
        const allInputs = document.querySelectorAll('#skills-container textarea');
        let allFilled = true;
    
        // Verifica que todos los inputs tengan contenido
        allInputs.forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('input-empty'); // Resalta inputs vacíos
                allFilled = false;
            } else {
                input.classList.remove('input-empty'); // Limpia estilos si tiene contenido
            }
        });
    
        // Verifica si hay al menos 3 habilidades y todas están llenas
        if (allInputs.length < 3) {
            e.preventDefault();
            alert('Por favor, agrega al menos 3 habilidades antes de continuar.');
            return;
        }
    
        if (!allFilled) {
            e.preventDefault();
            alert('Por favor, asegúrate de que todas las habilidades estén llenas.');
        }
    });

    // Evento para agregar un nuevo input
    addSkillBtn.addEventListener('click', createSkillInput);
});