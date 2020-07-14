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
            title="Add Header"
            @on-visible-change="detectClose">

            <!-- Form -->
            <Form ref="headerForm" :model="headerForm" :rules="headerFormRules">

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="headerForm.name" placeholder="price" maxlength="50" 
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
                        :value="headerForm.value"
                        sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
                    </textOrCodeEditor>
                </FormItem>
                
            </Form>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Add Header</Button>
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
            headers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                headerForm: null,
                headerFormRules: {
                    /** Note: The getValidVariableNameValidator() is a method defined within
                     *  mixin.js via the globalCustomMixin while the getUniqueNameValidator()
                     *  and getHasValueValidator() are defined within localMixin.js via the 
                     *  localCustomMixin
                     */
                    name: [
                        { required: true, message: 'Please enter your header', trigger: 'blur' },
                        { validator: this.getUniqueNameValidator(), trigger: 'change' }
                    ],
                    value: [
                        { validator: this.getHasValueValidator(), trigger: 'change' },
                    ],
                }
            }
        },
        methods: {

            getHeaderForm(){

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
                //  Validate the header form
                this.$refs['headerForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        //  Add the header
                        this.addHeaderOption();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your header yet',
                            duration: 6
                        });
                    }
                })
            },
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            addHeaderOption(){
                
                //  Add the given header
                this.headers.push( this.headerForm );

                this.$Message.success({
                    content: 'Header added!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();
            }
        },
        created(){
            this.headerForm = this.getHeaderForm();
        }
    }
</script>