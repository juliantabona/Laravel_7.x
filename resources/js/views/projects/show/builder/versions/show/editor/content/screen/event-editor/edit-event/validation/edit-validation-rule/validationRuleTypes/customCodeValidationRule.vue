<template>
           
    <!-- Custom Validation Code Editor -->
    <customEditor
        :useCodeEditor="true"
        :codeContent="validationRule.value"
        @codeChange="validationRule.value = $event"
        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
    </customEditor>    

</template>

<script>

    import customEditor from './../../../../.././../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
        props: {
            validationRule: {
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
