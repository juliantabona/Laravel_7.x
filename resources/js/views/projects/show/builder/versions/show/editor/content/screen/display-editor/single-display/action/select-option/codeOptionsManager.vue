<template>
    


    <Row :gutter="12">
        
        <Col :span="24">

            <div class="bg-grey-light border my-3 py-3 px-2">

                <!-- Code Editor -->
                <customEditor
                    :useCodeEditor="true"
                    sampleCodeTemplate="ussd_service_select_options_action_sample_code"
                    :codeContent="display.content.action.select_option.code_editor_options.code_editor_text"
                    @codeChange="display.content.action.select_option.code_editor_options.code_editor_text = $event">
                </customEditor>

            </div>
                
            <!-- Code Options Reference Name -->
            <div class="bg-grey-light border my-3 py-3 px-2">
                <div class="d-flex mt-3 mb-2">

                    <span class="font-weight-bold mt-1 mr-2">Reference:</span>

                    <!-- Reference Name Input -->
                    <referenceNameInput 
                        v-model="display.content.action.select_option.code_editor_options.reference_name" class="w-100"
                        :builder="builder" :screen="screen" :display="display">
                    </referenceNameInput>

                </div>

            </div>

            <!-- Messages / Desclaimers -->
            <div class="bg-grey-light border my-3 py-3 px-2">

                <div class="mb-3">
                    
                    <textOrCodeEditor
                        title="No Options Message"
                        :placeholder="'No options found'"
                        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code"
                        :value="display.content.action.select_option.code_editor_options.no_results_message">
                    </textOrCodeEditor>

                </div>

                <div class="mb-3">
                    
                    <textOrCodeEditor
                        title="Incorrect Option Message"
                        :placeholder="'Incorrect option selected'"
                        sampleCodeTemplate="ussd_service_select_option_incorrect_option_selected_msg_sample_code"
                        :value="display.content.action.select_option.code_editor_options.incorrect_option_selected_message">
                    </textOrCodeEditor>

                </div>

            </div>
        
        </Col>

    </Row>

</template>

<script>

    import textOrCodeEditor from './../../../../textOrCodeEditor.vue';
    import referenceNameInput from './../../../../referenceNameInput.vue';
    import customEditor from './../../../../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { textOrCodeEditor, referenceNameInput, customEditor },
        props: { 
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
        },
        computed: {
            groupReferenceUsesCodeEditorMode(){
                return this.display.content.action.select_option.code_editor_options.group_reference.code_editor_mode
            }
        },
    };
  
</script>