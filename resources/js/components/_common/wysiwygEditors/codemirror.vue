<template>

    <codemirror 
        v-model="localValue"
        :options="codeOptions"
        @input="$emit('codeChange', $event)">
    </codemirror>

</template>

<script>

    // require component
    import { codemirror } from 'vue-codemirror'

    // require styles
    import 'codemirror/lib/codemirror.css'

    // language
    import 'codemirror/mode/php/php.js'
    
    // theme css
    import 'codemirror/theme/cobalt.css'

    export default {
        props: {
            value: {
                type: String,
                default: ''
            },
            options: {
                type: Object,
                default: null
            }
        },
        components: { codemirror },
        data () {
            return {
                localValue: this.value
            }
        },    
        computed: {
            codeOptions(){

                //  Set the default options then overide with custom options if any
                return Object.assign({}, {
                    tabSize: 4,
                    line: true,
                    theme: 'cobalt',
                    lineNumbers: true,
                    lineWrapping: true,
                    styleActiveLine: true,
                    mode: 'application/x-httpd-php'
                }, this.options);
                
            }
        }
    }
</script>