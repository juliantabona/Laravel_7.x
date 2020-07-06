<template>
                            
    <Row :gutter="12">
        
        <Col v-if="formattingRule.value" :span="fullWidthLayout ? '24' : '12'" class="mb-2">
        
            <textOrCodeEditor
                size="small"
                :placeholder="'3'"
                :title="valueTitle"
                :value="formattingRule.value"
                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
            </textOrCodeEditor>

        </Col>
        
        <Col v-if="formattingRule.value_2" :span="fullWidthLayout ? '24' : '12'">
        
            <textOrCodeEditor
                size="small"
                :placeholder="'3'"
                :title="value2Title"
                :value="formattingRule.value_2"
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
            valueTitle(){
                switch (this.formattingRule.type) {
                    case 'in_between_including':
                        return 'Min';
                        break;
                    case 'in_between_excluding':
                        return 'Min';
                        break;
                    default:
                        return this.formattingRule.name;
                        break;
                }
            },
            value2Title(){
                switch (this.formattingRule.type) {
                    case 'in_between_including':
                        return 'Max';
                        break;
                    case 'in_between_excluding':
                        return 'Max';
                        break;
                    default:
                        return this.formattingRule.name;
                        break;
                }
            },
            fullWidthLayout(){
                //  If we are using code editor on the first or second value, then display full width layout
                return (this.formattingRule.value.code_editor_mode || (this.formattingRule.value_2 || {}).code_editor_mode);
            }
        }
    }

</script>
