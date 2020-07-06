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
            <Form ref="validationRuleForm" class="mb-4" :model="validationRuleForm" :rules="validationRuleFormRules">

                <Row :gutter="12">

                    <Col :span="validationRuleForm.active.code_editor_mode ? 24 : 16">

                        <!-- Enter Name -->
                        <FormItem prop="name" class="mb-2">
                            <Input  type="text" v-model="validationRuleForm.name" placeholder="Validation Rule name" 
                                    :disabled="validationRuleForm.type != 'custom_regex'">
                                <span slot="prepend">Name</span>
                            </Input>
                        </FormItem>

                    </Col>

                    <Col :span="validationRuleForm.active.code_editor_mode ? 24 : 8">
                    
                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateCheckbox v-model="validationRuleForm.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Validation Error Message -->
                        <FormItem prop="error_msg" class="mb-2">
                            
                            <textOrCodeEditor
                                size="small"
                                title="Validation Message"
                                :value="validationRuleForm.error_msg"
                                :placeholder="'Enter validation message'"
                                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                            </textOrCodeEditor>
                            
                        </FormItem>

                        <!-- Enter Comment -->
                        <commentInput v-model="validationRuleForm.comment" class="mt-2"></commentInput>

                    </Col>

                </Row>

            </Form>
            
            <!-- Edit Custom Regex Validation Rule --> 
            <customRegexValidationRule 
                v-if="validationRuleForm.type == 'custom_regex'" 
                :validationRule="validationRuleForm">
            </customRegexValidationRule>
            
            <!-- Edit Basic Validation Rule --> 
            <basicValidationRule 
                v-else-if="validationRuleForm.value" 
                :validationRule="validationRuleForm">
            </basicValidationRule>
            
            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="validationRuleForm.hexColor" recommend></ColorPicker>
                </span>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Save Changes</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import modalMixin from './../../../../../../../../../../../../../components/_mixins/modal/main.vue';

    import activeStateCheckbox from './../../../../../../../editor/content/screen/activeStateCheckbox.vue';
    import customRegexValidationRule from './validationRuleTypes/customRegexValidationRule.vue';
    import basicValidationRule from './validationRuleTypes/basicValidationRule.vue';
    import textOrCodeEditor from './../../../../textOrCodeEditor.vue';
    import commentInput from './../../../../commentInput.vue';

    export default {
        mixins: [modalMixin],
        components: { 
            activeStateCheckbox, customRegexValidationRule, basicValidationRule, textOrCodeEditor, commentInput
        },
        props: {
            index: {
                type: Number,
                default: null
            },
            validationRule: {
                type: Object,
                default: null
            },
            validationRules: {
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
            return {
                validationRuleForm: null,
                validationRuleFormRules: {
                    name: [
                        { required: true, message: 'Please enter your validation rule name', trigger: 'blur' },
                        { min: 3, message: 'Validation rule name is too short', trigger: 'change' },
                        { max: 50, message: 'Validation rule name is too long', trigger: 'change' }
                    ]
                },
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Validation Rule';

                }else if( this.isCloning ){

                    return 'Clone Validation Rule';
                
                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }

            }
        },
        methods: {
            
            getvalidationRuleForm(){
                
                return _.cloneDeep(this.validationRule);

            },
            handleSubmit(){
                
                //  Validate the validation Rule form
                this.$refs['validationRuleForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditValidationRule();

                        }else if( this.isCloning ){
                        
                            this.handleCloneValidationRule();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot save your validation rule yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditValidationRule(){

                this.$set(this.validationRules, this.index, this.validationRuleForm);

                this.$Message.success({
                    content: 'Validation rule updated!',
                    duration: 6
                });

            },
            handleCloneValidationRule(){

                //  Add the cloned validation rule to the rest of the other validation rules
                this.validationRules.push(this.validationRuleForm);

                this.$Message.success({
                    content: 'Validation rule cloned!',
                    duration: 6
                });

            }
        },
        created(){
            this.validationRuleForm = this.getvalidationRuleForm();
        }
    }
</script>