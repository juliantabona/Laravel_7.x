<template>
    <div>
        <!-- Modal 

             Note: modalVisible and detectClose() are imported from the modalMixin.
             They are used to allow for opening and closing the modal properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to modalMixin.
        -->
        <Modal
            width="600"
            :title="modalTitle"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <Alert v-if="isEditing" show-icon>Editing</Alert>

            <Alert v-else-if="isCloning" show-icon>Cloning</Alert>

            <!-- Form -->
            <Form ref="arrayValueForm" class="mb-4" :model="arrayValueForm" :rules="arrayValueFormRules">

                <Row :gutter="12">

                    <Col :span="8" :offset="16">
                    
                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateCheckbox v-model="arrayValueForm.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Value -->
                        <FormItem prop="value">
                            <textOrCodeEditor
                                size="small"
                                title="Value"
                                :value="arrayValueForm.value"
                                :placeholder="'{{ product.id }}'"
                                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
                            </textOrCodeEditor>
                        </FormItem>

                    </Col>

                    <!-- Default value selector -->
                    <Col :span="24" class="d-flex mb-2">

                        <span class="font-weight-bold mt-1 mr-2">Default:</span>

                        <!-- Default Type -->
                        <Select v-model="arrayValueForm.on_empty_value.default.selected_type">
                            <Option v-for="(type, index) in defaultTypes" :value="type.value" :key="index">
                                {{ type.name }}
                            </Option>
                        </Select>

                    </Col>

                    <!-- Custom default value input -->
                    <Col :span="24" v-if="arrayValueForm.on_empty_value.default.selected_type == 'custom'">

                        <!-- Default Value -->
                        <textOrCodeEditor
                            size="small"
                            class="mt-3 mb-2"
                            title="Default Value"
                            :placeholder="'{{ product.id }}'"
                            :value="arrayValueForm.on_empty_value.default.custom"
                            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                        </textOrCodeEditor>

                    </Col>

                    <Col :span="24" class="mt-2">

                        <!-- Enter Comment -->
                        <commentInput v-model="arrayValueForm.comment"></commentInput>

                    </Col>

                </Row>

            </Form>
            
            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="arrayValueForm.hexColor" recommend></ColorPicker>
                </span>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">{{ modalOkBtnText }}</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    //  Get the custom mixin files
    import modalMixin from './../../../../../../../../../../../../../components/_mixins/modal/main.vue';
    var globalCustomMixin = require('./../../../../../../../../../../../../../mixin.js').default;
    var localCustomMixin = require('./../localMixin.js').default;

    import activeStateCheckbox from './../../../../../../../editor/content/screen/activeStateCheckbox.vue';
    import textOrCodeEditor from './../../../../textOrCodeEditor.vue';
    import commentInput from './../../../../commentInput.vue';

    export default {
        mixins: [modalMixin, globalCustomMixin, localCustomMixin],
        components: { 
            activeStateCheckbox, textOrCodeEditor, commentInput
        },
        props: {
            index: {
                type: Number,
                default: null
            },
            arrayValue: {
                type: Object,
                default: null
            },
            arrayValues: {
                type: Array,
                default: () => []
            },
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
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
        },
        data(){

            //  Custom validation to detect if key value value has been provided
            const hasValueValidator = (rule, value, callback) => {

                //  If we are not using code editor mode and the value has not been provided
                if ( !this.arrayValueForm.value.code_editor_mode && !this.arrayValueForm.value.text) {
                    callback(new Error('Please enter your value'));
                } else {
                    callback();
                }
            };

            return {
                arrayValueForm: null,
                arrayValueFormRules: {
                    value: [
                        { validator: hasValueValidator, trigger: 'change' },
                    ],
                }
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Array Value';

                }else if( this.isCloning ){

                    return 'Clone Array Value';
                
                }else {

                    return 'Add Array Value';
                
                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }else{

                    return 'Add';
                }

            }
        },
        methods: {
            
            getArrayValueTemplate(){

                //  Set the default form details
                return { 
                    value:{
                        text: '',
                        code_editor_text: '',
                        code_editor_mode: false
                    },
                    on_empty_value: {
                        default: {
                            selected_type: 'null',            //  text_input, number_input, true, false, null, empty_array
                            custom: {
                                text: '',                     //  e.g "{{ product.quantity }}"
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                        }
                    },
                    active: {
                        text: true,
                        code_editor_text: '',
                        code_editor_mode: false
                    },
                    comment: '',
                    hexColor: '#CECECE'
                }

            },
            getarrayValueForm(){
                return _.cloneDeep( Object.assign({}, this.getArrayValueTemplate(), this.arrayValue) );
            },
            handleSubmit(){
                console.log('Stage 1');
                //  Validate the array value form
                this.$refs['arrayValueForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                console.log('Stage 2');

                        if( this.isEditing ){
                        
                            this.handleEditArrayValue();

                        }else if( this.isCloning ){
                        
                            this.handleCloneArrayValue();

                        }else{

                console.log('Stage 3');
                            //  Add the array value
                            this.handleAddArrayValueOption();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot save your array value yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditArrayValue(){

                this.$set(this.arrayValues, this.index, this.arrayValueForm);

                this.$Message.success({
                    content: 'Array value updated!',
                    duration: 6
                });

            },
            handleCloneArrayValue(){

                //  Add the cloned array value to the rest of the other array values
                this.arrayValues.push(this.arrayValueForm);

                this.$Message.success({
                    content: 'Array value cloned!',
                    duration: 6
                });

            },
            handleAddArrayValueOption(){
                
                console.log('Stage 4');
                console.log('this.arrayValues');
                console.log(this.arrayValues);
                console.log('this.arrayValueForm');
                console.log(this.arrayValueForm);

                //  Add the given array value
                this.arrayValues.push( this.arrayValueForm );

                this.$Message.success({
                    content: 'Array value added!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();
            }
        },
        created(){
            this.arrayValueForm = this.getarrayValueForm();
        }
    }
</script>