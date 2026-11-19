<script>
    // Wraps every password field on the page with a show/hide toggle —
    // applied globally here rather than per-form so any password field
    // added to any page (now or later) gets one automatically, with
    // nothing to remember to wire up on new forms.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.dataset.hasVisibilityToggle) return;
            input.dataset.hasVisibilityToggle = '1';

            var wrapper = document.createElement('div');
            wrapper.className = 'relative';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            input.classList.add('pr-10');

            var button = document.createElement('button');
            button.type = 'button';
            button.setAttribute('aria-label', 'Show password');
            button.className = 'absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-brand-orange';
            button.innerHTML = '<i class="fa-solid fa-eye"></i>';

            button.addEventListener('click', function () {
                var isShowing = input.type === 'text';
                input.type = isShowing ? 'password' : 'text';
                button.innerHTML = isShowing ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
                button.setAttribute('aria-label', isShowing ? 'Show password' : 'Hide password');
            });

            wrapper.appendChild(button);
        });
    });
</script>
