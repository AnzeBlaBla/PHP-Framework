<script>
    const ACTION_MODE = {
        FETCH: 0,
        REDIRECT: 1
    }

    const DEFAULT_ACTION_MODE = ACTION_MODE.REDIRECT;

    // This is a helper function to set innerHTML and execute scripts inside
    function setInnerHTML(elm, html) {
        elm.innerHTML = html;

        Array.from(elm.querySelectorAll("script"))
            .forEach(oldScriptEl => {
                const newScriptEl = document.createElement("script");

                Array.from(oldScriptEl.attributes).forEach(attr => {
                    newScriptEl.setAttribute(attr.name, attr.value)
                });

                const scriptText = document.createTextNode(oldScriptEl.innerHTML);
                newScriptEl.appendChild(scriptText);

                oldScriptEl.parentNode.replaceChild(newScriptEl, oldScriptEl);
            });
    }

    function callSpecialFunction(id) {
        return function(...args) {
            if (DEFAULT_ACTION_MODE == ACTION_MODE.FETCH) {
                /* This page with post JSON data for function call */
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'callSpecialFunction',
                        specialFunctionID: id,
                        args: args
                    })
                }).then(function(response) {
                    return response.text();
                }).then(function(text) {
                    setInnerHTML(document.body, text);
                });
            } else if (DEFAULT_ACTION_MODE == ACTION_MODE.REDIRECT) {
                // Send user to page with post data
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'data';
                input.value = JSON.stringify({
                    action: 'callSpecialFunction',
                    specialFunctionID: id,
                    args: args
                });

                form.appendChild(input);

                document.body.appendChild(form);

                form.submit();

                document.body.removeChild(form);
            }
        }
    }

    function getFormData(target) {
        const data = {};
        const elements = target.elements;
        for (let i = 0; i < elements.length; i++) {
            const element = elements[i];
            if (element.name !== '') {
                data[element.name] = element.value;
            }
        }
        return data;
    }


    class FrameworkComponent extends HTMLElement {

        uniqueID = null;
        component = null;

        shadow = null;

        constructor() {
            // Always call super first in constructor
            super();

            // Create a shadow root
            this.shadow = this.attachShadow({
                mode: 'open'
            });
        }

        connectedCallback() {
            const uniqueID = this.getAttribute('uniqueid');
            this.uniqueID = uniqueID;

            const component = this.getAttribute('component');
            this.component = component;

            console.log('component', component, 'uniqueID', uniqueID, this);

            // Don't use document, because this element could be in a shadow root
            const root = this.getRootNode();
            const template = root.getElementById(`template-${uniqueID}`);
            const instance = template.content.cloneNode(true);
            this.shadow.appendChild(instance);

            // remove template
            template.remove();
        }

        static get observedAttributes() {
            return ['uniqueid', 'component'];
        }
    }

    customElements.define('framework-component', FrameworkComponent);
</script>