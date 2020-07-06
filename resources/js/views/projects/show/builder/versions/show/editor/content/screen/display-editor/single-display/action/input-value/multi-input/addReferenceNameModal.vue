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
            title="Add Reference Name"
            @on-visible-change="detectClose">

            <!-- Form -->
            <Form ref="referenceForm" :model="referenceForm" :rules="referenceFormRules">

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="referenceForm.name" placeholder="first_name" maxlength="50" 
                            show-word-limit @keyup.enter.native="handleSubmit()" v-focus="'input'">
                            <span slot="prepend">Name</span>
                    </Input>
                </FormItem>
                
            </Form>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Add Reference Name</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import modalMixin from './../../../../../../../../../../../../../../components/_mixins/modal/main.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../../../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
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
                default:() => {}
            },
        },
        data(){

            //  Custom validation to detect matching reference names
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if reference names with the same name exist
                var similarNamesExist = this.display.content.action.input_value.multi_value_input.reference_names.filter( (reference_name) => { 
                    //  If the given value matches the reference name
                    return (value == reference_name);
                }).length;

                //  If reference names with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This reference name is already in use'));
                } else {
                    callback();
                }
            };

            //  Custom validation to detect if the name has white spaces
            const namesWithSpacesValidator = (rule, value, callback) => {
                
                //  This pattern to detect white spaces
                var pattern = /\s/; 

                //  Check pattern
                if ( pattern.test(value) == true ) {
                    callback(new Error('This reference name must not have spaces. Use underscores "_" instead e.g "first_name", "_username", "age_less_than_30"'));
                } else {
                    callback();
                }
            };

            //  Custom validation to detect if the name starts with characters that are not letters or underscores
            const validFirstCharacterValidator = (rule, value, callback) => {
                
                //  This pattern will detect if the value starts with a character that is not a letter or underscore
                var pattern = /^[^a-zA-Z_]/;

                //  Check pattern
                if ( pattern.test(value) == true ) {
                    callback(new Error('This reference name must start with a letter or underscore "_" e.g "first_name", "_username", "age_less_than_30"'));
                } else {
                    callback();
                }
            };

            //  Custom validation to detect if the characters after the first character are letters, numbers or underscores only
            const validCharactersAfterFirstCharacterValidator = (rule, value, callback) => {
                
                /** This pattern matches any non-word character. Same as [^a-zA-Z_0-9].
                 *  Note that a word is definned as a to z, A to Z, 0 to 9, and the 
                 *  underscore "_"
                 */
                var pattern = /\W/g;

                //  Check pattern
                if ( pattern.test(value.substring(1)) == true ) {
                    callback(new Error('This reference name must only contain letters, numbers and underscores "_" e.g "first_name", "_username", "age_less_than_30"'));
                } else {
                    callback();
                }
            };

            return {
                referenceForm: null,
                referenceFormRules: {
                    name: [
                        { required: true, message: 'Please enter your reference name', trigger: 'blur' },
                        { min: 3, message: 'Reference name is too short', trigger: 'change' },
                        { max: 50, message: 'Reference name is too long', trigger: 'change' },
                        { validator: namesWithSpacesValidator, trigger: 'change' },
                        { validator: validFirstCharacterValidator, trigger: 'change' },
                        { validator: validCharactersAfterFirstCharacterValidator, trigger: 'change' },
                    ],
                }
            }
        },
        methods: {

            getReferenceForm(){

                //  Set the default form details
                return { 
                    name: '' 
                }

            },
            handleSubmit(){
                //  Validate the reference name form
                this.$refs['referenceForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        //  Add the reference name
                        this.addReferenceName();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your reference name yet',
                            duration: 6
                        });
                    }
                })
            },
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            addReferenceName(){

                //  Add the given reference name
                this.display.content.action.input_value.multi_value_input.reference_names.push(this.referenceForm.name);

                //  Close the modal
                this.closeModal();
            }
        },
        created(){
            this.referenceForm = this.getReferenceForm();
        }
    }
</script>