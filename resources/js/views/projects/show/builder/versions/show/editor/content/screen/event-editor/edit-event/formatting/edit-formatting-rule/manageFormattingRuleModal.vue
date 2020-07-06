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
            <Form ref="formattingRuleForm" class="mb-4" :model="formattingRuleForm" :rules="formattingRuleFormRules">

                <Row :gutter="12">

                    <Col :span="formattingRuleForm.active.code_editor_mode ? 24 : 16">

                        <!-- Enter Name -->
                        <FormItem prop="name" class="mb-2">
                            <Input  type="text" v-model="formattingRuleForm.name" placeholder="Formatting Rule name" 
                                    :disabled="formattingRuleForm.type != 'custom_format'">
                                <span slot="prepend">Name</span>
                            </Input>
                        </FormItem>

                    </Col>

                    <Col :span="formattingRuleForm.active.code_editor_mode ? 24 : 8">
                    
                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateCheckbox v-model="formattingRuleForm.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Comment -->
                        <commentInput v-model="formattingRuleForm.comment" class="mt-2"></commentInput>

                    </Col>

                </Row>

            </Form>
            
            <!-- Edit Custom Regex Formatting Rule --> 
            <customFormattingRule 
                v-if="formattingRuleForm.type == 'custom_format'" 
                :formattingRule="formattingRuleForm">
            </customFormattingRule>
            
            <!-- Edit Basic Formatting Rule --> 
            <basicFormattingRule 
                v-else-if="formattingRuleForm.value" 
                :formattingRule="formattingRuleForm">
            </basicFormattingRule>
            
            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="formattingRuleForm.hexColor" recommend></ColorPicker>
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
    import customFormattingRule from './formattingRuleTypes/customFormattingRule.vue';
    import basicFormattingRule from './formattingRuleTypes/basicFormattingRule.vue';
    import textOrCodeEditor from './../../../../textOrCodeEditor.vue';
    import commentInput from './../../../../commentInput.vue';

    export default {
        mixins: [modalMixin],
        components: { 
            activeStateCheckbox, customFormattingRule, basicFormattingRule, textOrCodeEditor, commentInput
        },
        props: {
            index: {
                type: Number,
                default: null
            },
            formattingRule: {
                type: Object,
                default: null
            },
            formattingRules: {
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
                formattingRuleForm: null,
                formattingRuleFormRules: {
                    name: [
                        { required: true, message: 'Please enter your formatting rule name', trigger: 'blur' },
                        { min: 3, message: 'Formatting rule name is too short', trigger: 'change' },
                        { max: 50, message: 'Formatting rule name is too long', trigger: 'change' }
                    ]
                },
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Formatting Rule';

                }else if( this.isCloning ){

                    return 'Clone Formatting Rule';
                
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
            
            getformattingRuleForm(){
                
                return _.cloneDeep(this.formattingRule);

            },
            handleSubmit(){
                
                //  Validate the formatting Rule form
                this.$refs['formattingRuleForm'].validate((valid) => 
                {   
                    //  If the formatting passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditFormattingRule();

                        }else if( this.isCloning ){
                        
                            this.handleCloneFormattingRule();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the formatting failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot save your formatting rule yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditFormattingRule(){

                this.$set(this.formattingRules, this.index, this.formattingRuleForm);

                this.$Message.success({
                    content: 'Formatting rule updated!',
                    duration: 6
                });

            },
            handleCloneFormattingRule(){

                //  Add the cloned formatting rule to the rest of the other formatting rules
                this.formattingRules.push(this.formattingRuleForm);

                this.$Message.success({
                    content: 'Formatting rule cloned!',
                    duration: 6
                });

            }
        },
        created(){
            this.formattingRuleForm = this.getformattingRuleForm();
        }
    }
</script>