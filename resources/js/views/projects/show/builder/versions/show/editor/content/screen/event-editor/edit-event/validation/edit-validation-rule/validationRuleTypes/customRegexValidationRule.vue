<template>
           
    <!-- Custom Validation Regex Rule Input -->
    <textOrCodeEditor
        size="small"
        title="Regex Rule"
        :placeholder="'/[a-zA-Z0-9]+/'"
        :value="validationRule.rule"
        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
    </textOrCodeEditor>     

</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
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
