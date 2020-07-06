<template>
    
    <Row :gutter="12">

        <Col :span="24">

            <!-- Static Option List & Dragger  -->
            <draggable
                :style="draggableStyles"
                :list="options"
                @start="drag=true" 
                @end="drag=false" 
                :options="{
                    group:'static-options', 
                    handle:'.dragger-handle',
                    draggable:'.single-draggable-item',
                }">

                <!-- Single Static Option  -->
                <singleStaticOption v-for="(option, index) in options" :key="index"
                    :options="options"
                    :builder="builder"
                    :display="display"
                    :screen="screen"
                    :option="option"
                    :index="index">
                </singleStaticOption>
                
                <!-- No options message -->
                <Alert v-if="!optionsExist" type="info" class="mb-0" style="width:300px;" show-icon>No Options Found</Alert>

            </draggable>

            <div class="clearfix">

                <!-- Add Static Option Button -->
                <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                             class="float-right" :ripple="!optionsExist"
                             @click.native="handleAddStaticOption()">
                    <span>Add Option</span>
                </basicButton>

            </div>

        </Col>
        
        <Col :span="24">

            <div class="d-flex mt-3 mb-2">

                <span class="font-weight-bold mt-1 mr-2">Reference:</span>

                <!-- Reference Name Input -->
                <referenceNameInput 
                    v-model="display.content.action.select_option.static_options.reference_name"
                    :builder="builder" :screen="screen" :display="display"
                    :isRequired="false">
                </referenceNameInput>

            </div>

            <div class="mb-3">
                
                <textOrCodeEditor
                    title="No Options Message"
                    :placeholder="'No options found'"
                    sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code"
                    :value="display.content.action.select_option.static_options.no_results_message">
                </textOrCodeEditor>

            </div>

            <div class="mb-3">
                
                <textOrCodeEditor
                    title="Incorrect Option Message"
                    :placeholder="'Incorrect option selected'"
                    sampleCodeTemplate="ussd_service_select_option_incorrect_option_selected_msg_sample_code"
                    :value="display.content.action.select_option.static_options.incorrect_option_selected_message">
                </textOrCodeEditor>

            </div>
            
        </Col>

        <!-- 
            MODAL TO ADD / CLONE / EDIT STATIC OPTION
        -->
        <template v-if="isOpenManageStaticOptionModal">

            <manageStaticOptionModal
                :screen="screen"
                :display="display"
                :builder="builder"
                :options="options"
                :isCloning="false"
                :isEditing="false"
                @visibility="isOpenManageStaticOptionModal = $event">
            </manageStaticOptionModal>
    
        </template>

    </Row>

</template>

<script>

    import draggable from 'vuedraggable';
    
    import singleStaticOption from './single-option/main.vue';
    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import referenceNameInput from './../../../../../referenceNameInput.vue';
    import manageStaticOptionModal from './single-option/manageStaticOptionModal.vue';
    import basicButton from './../../../../../../../../../../../../../../components/_common/buttons/basicButton.vue';

    export default {
        components: { 
            draggable, singleStaticOption, textOrCodeEditor, referenceNameInput, 
            manageStaticOptionModal, basicButton 
        },
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
        data(){
            return {
                isOpenManageStaticOptionModal: false,
                options: this.display.content.action.select_option.static_options.options
            }
        },
        computed: {
            totalOptions(){
                return this.options.length;
            },
            optionsExist(){
                return this.totalOptions ? true : false;
            },
            addButtonType(){
                return this.optionsExist ? 'default' : 'success';
            },
            draggableStyles(){
                return  this.optionsExist ? {} : {
                    padding: '10px',
                    minHeight: '50px',
                    borderRadius: '9px',
                    marginBottom: '20px',
                    background: '#f9f9f9',
                    border: '1px dashed #c5c5c5'
                }
            }
        },
        methods: {
            handleAddStaticOption(){
                this.isOpenManageStaticOptionModal = true;
            },
        },
    };
  
</script>