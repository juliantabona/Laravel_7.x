<template>

    <div class="w-100" :style="divStyles">

        <!-- Code Editor -->
        <codemirror 
            v-if="useCodeEditor"
            v-model="localCodeContent" 
            :options="codeMirrorOptions"
            @input="$emit('codeChange', $event)">
        </codemirror>

        <!-- Editor -->
        <div v-else
            :style="styles"
            v-html="localContent"
            :placeholder="placeholder"
            :class="'editable-content-field editable-content-field-' + this.size + (classes ? ' '+classes : '')"
            @blur="handleBlur($event.target.innerText)"
            @focus="handleFocus($event.target.innerText)"
            :contenteditable="contenteditable" :resize="resize">
        </div>

    </div>

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
            content: {
                type: String,
                default: ''
            },
            codeContent: {
                type: String,
                default: ''
            },
            useCodeEditor: {
                type: Boolean,
                default: true
            },
            size: {
                type: String,
                default: 'medium'
            },
            sampleCodeTemplate: {
                type: String,
                default: ''
            },
            resize: {
                type: Boolean,
                default: true
            },
            contenteditable: {
                type: Boolean,
                default: true
            },
            classes: {
                type: String,
                default: null
            },
            innerClasses: {
                type: Array,
                default: function(){
                    return [];
                }
            },
            styles: {
                type: Object,
                default: function(){
                    return {};
                }
            },
            placeholder: {
                type: String,
                default: 'Wite something...'
            }
        },
        components: { codemirror },
        data () {
            return {
                localContent: '',
                localCodeContent: '',
                codeMirrorOptions: {
                    tabSize: 4,
                    line: true,
                    theme: 'cobalt',
                    lineNumbers: true,
                    lineWrapping: true,
                    styleActiveLine: true,
                    mode: 'application/x-httpd-php'
                }
            }
        },
        watch: {

            //  Watch for changes on the content
            content: {
                handler: function (val, oldVal) {

                    if(val != this.localContent){

                        //  Update the local content value
                        this.handleBlur(val);

                    }

                }
            },

            //  Watch for changes on the codeContent
            codeContent: {
                handler: function (val, oldVal) {

                    //  If the new value and the current local code content are not the same
                    if(val != this.localCodeContent){

                        this.localCodeContent = this.getCodeContent( val );

                    }

                }
            }
        },       
        computed: {
            divStyles(){
                return this.useCodeEditor ? { borderRadius: '5px', overflow: 'hidden' } : {} ;
            }
        },
        methods: {
            getCodeContent(code){
                //  If the new value is not empty or null
                if( code != '' && code != null ){

                    //  Update the current local code content
                    return code;

                }else{
                    if( this.sampleCodeTemplate ){

                        //  Get the example code samples
                        var codeSamples = require('./example-code-samples.js');

                        //  Set custom placeholder code
                        return codeSamples.default[this.sampleCodeTemplate] || 'No sample code found';
                        
                    }
                }
            },
            handleBlur(currentContent){
                    
                this.localContent = this.handleDynamicContent(currentContent);

                this.$emit('contentChange', this.localContent);

            },
            handleFocus(currentContent){
                    
                this.localContent = this.handleDynamicContent(currentContent, false);

                this.$emit('contentChange', this.localContent);

            },
            handleDynamicContent(text = '', addHtmlToMustacheSyntax = true){

                //  Insert dynamic content inside curly braces within span tags with special styles
                function wrapInHTMLTags(match, offset, string){
                    
                    return '<span class="dynamic-content-label">' + match + '</span>';

                }

                //  Replace all matches with nothing (An empty string)
                function replaceWithNothing(match, offset, string){
                    
                    return '';

                }

                //  Get the content to format
                var content = text;

                if( content ){

                    //  This pattern searches for any HTML tags e.g <span ...> or </span>
                    var pattern = /([<][a-zA-Z/!][^>]*[>])/g;
                    
                    //  Replace all HTML tags within the content string with nothing
                    content = content.replace(pattern, replaceWithNothing);

                    if( addHtmlToMustacheSyntax == true ){

                        //  This pattern searches for anything using curly braces e.g {{ company }}
                        var pattern = /[{]{2}[\s]*[a-zA-Z_]{1}[a-zA-Z0-9_\.]{0,}[\s]*[}]{2}/g;

                        //  Wrap content with curly braces in HTML tags 
                        content = content.replace(pattern, wrapInHTMLTags);
                        
                    }

                }

                //  Return the formatted content
                return content;
                
            }
        },
        created(){        

            //  Update the local content value
            this.handleBlur(this.content);

            //  Update the local code content value
            this.localCodeContent = this.getCodeContent(this.codeContent);

        }
    }
</script>