<template>
                            
    <Row :gutter="12">
        
        <Col v-if="formattingRule.value" :span="fullWidthLayout ? '24' : '12'" class="mb-2">
        
            <textOrCodeEditor
                size="small"
                :placeholder="'3'"
                :value="formattingRule.value"
                :title="formattingRule.value_name"
                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
            </textOrCodeEditor>

        </Col>
        
        <Col v-if="formattingRule.value_2" :span="fullWidthLayout ? '24' : '12'">
        
            <textOrCodeEditor
                size="small"
                :placeholder="'3'"
                :value="formattingRule.value_2"
                :title="formattingRule.value_2_name"
                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
            </textOrCodeEditor>

        </Col>

    </Row>

</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
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
            fullWidthLayout(){
                //  If we are using code editor on the first or second value, then display full width layout
                return (this.formattingRule.value.code_editor_mode || (this.formattingRule.value_2 || {}).code_editor_mode);
            }
        }
    }

</script>
