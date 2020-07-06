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
            <Form ref="keyValueForm" class="mb-4" :model="keyValueForm" :rules="keyValueFormRules">

                <Row :gutter="12">

                    <Col :span="keyValueForm.active.code_editor_mode ? 24 : 16">

                        <!-- Enter Key Name -->
                        <FormItem prop="key" class="mb-2">
                            <Input  type="text" v-model="keyValueForm.key" placeholder="Key name">
                                <span slot="prepend">Key</span>
                            </Input>
                        </FormItem>

                    </Col>

                    <Col :span="keyValueForm.active.code_editor_mode ? 24 : 8">
                    
                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateCheckbox v-model="keyValueForm.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Value -->
                        <FormItem prop="value">
                            <textOrCodeEditor
                                size="small"
                                title="Value"
                                :value="keyValueForm.value"
                                :placeholder="'{{ product.id }}'"
                                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
                            </textOrCodeEditor>
                        </FormItem>

                    </Col>

                    <!-- Default value selector -->
                    <Col :span="24" class="d-flex mb-2">

                        <span class="font-weight-bold mt-1 mr-2">Default:</span>

                        <!-- Default Type -->
                        <Select v-model="keyValueForm.on_empty_value.default.selected_type">
                            <Option v-for="(type, index) in defaultTypes" :value="type.value" :key="index">
                                {{ type.name }}
                            </Option>
                        </Select>

                    </Col>

                    <!-- Custom default value input -->
                    <Col :span="24" v-if="keyValueForm.on_empty_value.default.selected_type == 'custom'">

                        <!-- Default Value -->
                        <textOrCodeEditor
                            size="small"
                            class="mt-3 mb-2"
                            title="Default Value"
                            :placeholder="'{{ product.id }}'"
                            :value="keyValueForm.on_empty_value.default.custom"
                            sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                        </textOrCodeEditor>

                    </Col>

                    <Col :span="24" class="mt-2">

                        <!-- Enter Comment -->
                        <commentInput v-model="keyValueForm.comment"></commentInput>

                    </Col>

                </Row>

            </Form>
            
            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="keyValueForm.hexColor" recommend></ColorPicker>
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
            keyValue: {
                type: Object,
                default: null
            },
            keyValues: {
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

            //  Custom validation to detect matching key names
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if key values with the same key name exist
                var similarNamesExist = this.keyValues.filter( (keyValue, index) => { 

                    //  Skip checking the current key value
                    if( this.index == index ){
                        return false;
                    }

                    //  If the given value matches the key name
                    return (value == keyValue.key);
                }).length;

                //  If key values with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This key value is already in use'));
                } else {
                    callback();
                }
            };

            //  Custom validation to detect if key value value has been provided
            const hasValueValidator = (rule, value, callback) => {

                //  If we are not using code editor mode and the value has not been provided
                if ( !this.keyValueForm.value.code_editor_mode && !this.keyValueForm.value.text) {
                    callback(new Error('Please enter your value'));
                } else {
                    callback();
                }
            };

            return {
                keyValueForm: null,
                keyValueFormRules: {
                    /** Note: The getValidVariableNameValidator() is a method defined within
                     *  mixin.js via the globalCustomMixin while the getUniqueNameValidator()
                     *  is defined within localMixin.js via the localCustomMixin
                     */
                    key: [
                        { required: true, message: 'Please enter your key name', trigger: 'blur' },
                        { validator: this.getValidVariableNameValidator(), trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' }
                    ],
                    value: [
                        { validator: hasValueValidator, trigger: 'change' },
                    ],
                }
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Key/Value Pair';

                }else if( this.isCloning ){

                    return 'Clone Key/Value Pair';
                
                }else {

                    return 'Add Key/Value Pair';
                
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
            
            getKeyValueTemplate(){

                //  Set the default form details
                return { 
                    key: '',
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
            getkeyValueForm(){
                return _.cloneDeep( Object.assign({}, this.getKeyValueTemplate(), this.keyValue) );
            },
            handleSubmit(){
                
                //  Validate the Key/Value form
                this.$refs['keyValueForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditKeyValue();

                        }else if( this.isCloning ){
                        
                            this.handleCloneKeyValue();

                        }else{

                            //  Add the key value
                            this.handleAddKeyValueOption();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot save your key/value pair yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditKeyValue(){

                this.$set(this.keyValues, this.index, this.keyValueForm);

                this.$Message.success({
                    content: 'Key value pair updated!',
                    duration: 6
                });

            },
            handleCloneKeyValue(){

                //  Add the cloned key/value pair to the rest of the other key/values
                this.keyValues.push(this.keyValueForm);

                this.$Message.success({
                    content: 'Key value pair cloned!',
                    duration: 6
                });

            },
            handleAddKeyValueOption(){
                
                //  Add the given key value
                this.keyValues.push( this.keyValueForm );

                this.$Message.success({
                    content: 'Key value pair added!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();
            }
        },
        created(){
            this.keyValueForm = this.getkeyValueForm();
        }
    }
</script>