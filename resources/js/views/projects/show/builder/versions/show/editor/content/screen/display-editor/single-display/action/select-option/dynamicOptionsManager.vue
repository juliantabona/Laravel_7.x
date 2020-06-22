<template>
    


    <Row :gutter="12">

        <!-- Foreach Items Group Reference -->
        <Col :span="groupReferenceUsesCodeEditorMode ? 24 : 12">
        
            <!-- Group Reference -->
            <textOrCodeEditor
                size="small"
                title="Foreach"
                :placeholder="'{{ items }}'"
                :value="display.content.action.select_option.dynamic_options.group_reference"
                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
            </textOrCodeEditor>

        </Col>
        
        <!-- As Label -->
        <Col :span="groupReferenceUsesCodeEditorMode ? 24 : 12" class="d-flex pt-4">

            <span class="d-block text-center mt-1 mr-3">As</span>
        
            <!-- Template Reference Name Input -->
            <referenceNameInput 
                v-model="display.content.action.select_option.dynamic_options.template_reference_name" class="w-100"
                :builder="builder" :screen="screen" :display="display">
            </referenceNameInput>
        
        </Col>
        
        <Col :span="24">

            <div class="bg-grey-light border my-3 py-3 px-2">

                <!-- Item Display Name -->
                <textOrCodeEditor
                    class="mb-2"
                    size="medium"
                    title="Display Name"
                    :placeholder="'{{ item.name }} - {{ item.price }}'"
                    sampleCodeTemplate="ussd_service_dynamic_select_option_display_name_sample_code"
                    :value="display.content.action.select_option.dynamic_options.template_display_name">
                </textOrCodeEditor>

                <!-- Item Value -->
                <textOrCodeEditor
                    size="medium"
                    title="Value"
                    :placeholder="'{{ item }}'"
                    sampleCodeTemplate="ussd_service_dynamic_select_option_value_sample_code"
                    :value="display.content.action.select_option.dynamic_options.template_value">
                </textOrCodeEditor>
                
                <!-- Item Reference Name -->
                <div class="d-flex mt-3 mb-2">

                    <span class="font-weight-bold mt-1 mr-2">Reference:</span>

                    <!-- Reference Name Input -->
                    <referenceNameInput 
                        v-model="display.content.action.select_option.dynamic_options.reference_name" class="w-100"
                        :builder="builder" :screen="screen" :display="display">
                    </referenceNameInput>

                </div>

            </div>

            <!-- Screen / Display Link -->
            <div class="bg-grey-light border my-3 py-3 px-2">

                <!-- Select Screen / Display Link -->
                <screenAndDisplaySelector 
                    :link="display.content.action.select_option.dynamic_options.link"
                    :builder="builder" :screen="screen" :display="display">
                </screenAndDisplaySelector>

            </div>

            <!-- Messages / Desclaimers -->
            <div class="bg-grey-light border my-3 py-3 px-2">

                <div class="mb-3">
                    
                    <textOrCodeEditor
                        title="No Options Message"
                        :placeholder="'No options found'"
                        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code"
                        :value="display.content.action.select_option.dynamic_options.no_results_message">
                    </textOrCodeEditor>

                </div>

                <div class="mb-3">
                    
                    <textOrCodeEditor
                        title="Incorrect Option Message"
                        :placeholder="'Incorrect option selected'"
                        sampleCodeTemplate="ussd_service_select_option_incorrect_option_selected_msg_sample_code"
                        :value="display.content.action.select_option.dynamic_options.incorrect_option_selected_message">
                    </textOrCodeEditor>

                </div>

            </div>
        
        </Col>

    </Row>

</template>

<script>

    import textOrCodeEditor from './../../../../textOrCodeEditor.vue';
    import referenceNameInput from './../../../../referenceNameInput.vue';
    import screenAndDisplaySelector from './../../../../screenAndDisplaySelector.vue';

    export default {
        components: { textOrCodeEditor, referenceNameInput, screenAndDisplaySelector },
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
                return this.display.content.action.select_option.dynamic_options.group_reference.code_editor_mode
            }
        },
    };
  
</script>