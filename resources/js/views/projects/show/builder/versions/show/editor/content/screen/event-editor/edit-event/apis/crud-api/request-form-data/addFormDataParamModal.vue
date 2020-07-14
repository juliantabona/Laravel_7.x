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
            v-model="modalVisible"
            title="Add Form Data Param"
            @on-visible-change="detectClose">

            <!-- Form -->
            <Form ref="formDataParamForm" :model="formDataParamForm" :rules="formDataParamFormRules">

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="formDataParamForm.name" placeholder="price" maxlength="50" 
                            show-word-limit @keyup.enter.native="handleSubmit()" v-focus="'input'">
                            <span slot="prepend">Name</span>
                    </Input>
                </FormItem>

                <!-- Enter Value -->
                <FormItem prop="value">
                    <textOrCodeEditor
                        size="small"
                        title="Value"
                        :placeholder="'420.00'"
                        :value="formDataParamForm.value"
                        sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
                    </textOrCodeEditor>
                </FormItem>
                
            </Form>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Add Form Data Param</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    //  Get the custom mixin files
    import modalMixin from './../../../../../../../../../../../../../../components/_mixins/modal/main.vue';
    var globalCustomMixin = require('./../../../../../../../../../../../../../../mixin.js').default;
    var localCustomMixin = require('./localMixin.js').default;

    export default {
        components: { textOrCodeEditor },
        mixins: [modalMixin, globalCustomMixin, localCustomMixin],
        props: {
            formDataParams: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                formDataParamForm: null,
                formDataParamFormRules: {
                    /** Note: The getValidVariableNameValidator() is a method defined within
                     *  mixin.js via the globalCustomMixin while the getUniqueNameValidator()
                     *  and getHasValueValidator() are defined within localMixin.js via the 
                     *  localCustomMixin
                     */
                    name: [
                        { required: true, message: 'Please enter your form data param', trigger: 'blur' },
                        { validator: this.getUniqueNameValidator(), trigger: 'change' }
                    ],
                    value: [
                        { validator: this.getHasValueValidator(), trigger: 'change' },
                    ],
                }
            }
        },
        methods: {

            getFormDataParamForm(){

                //  Set the default form details
                return { 
                    name: '',
                    value:{
                        text: '',
                        code_editor_text: '',
                        code_editor_mode: false
                    }
                }

            },
            handleSubmit(){
                //  Validate the form data param form
                this.$refs['formDataParamForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        //  Add the form data param
                        this.addFormDataParamOption();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your form data param yet',
                            duration: 6
                        });
                    }
                })
            },
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            addFormDataParamOption(){
                
                //  Add the given form data param
                this.formDataParams.push( this.formDataParamForm );

                this.$Message.success({
                    content: 'Form data param added!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();
            }
        },
        created(){
            this.formDataParamForm = this.getFormDataParamForm();
        }
    }
</script>