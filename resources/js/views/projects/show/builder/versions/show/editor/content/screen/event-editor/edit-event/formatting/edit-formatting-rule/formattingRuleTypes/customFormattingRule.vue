<template>
           
    <!-- Custom Formatting Code Editor -->
    <customEditor
        :useCodeEditor="true"
        :codeContent="formattingRule.value"
        @codeChange="formattingRule.value = $event"
        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
    </customEditor>    

</template>

<script>

    import customEditor from './../../../../.././../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
        props: {
            formattingRule: {
                type: Object,
                default:() => {}
            }
        },
        data(){
            return {
            }
        },
        computed: {
            
        },
        methods: {
            
        }
    }

</script>
