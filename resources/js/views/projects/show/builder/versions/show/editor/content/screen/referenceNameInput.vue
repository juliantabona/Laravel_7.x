<template>

    <!-- Form -->
    <Form ref="referenceForm" :model="referenceForm" :rules="referenceFormRules" class="mb-0">

        <!-- Reference Name Input -->
        <FormItem prop="name" class="mb-0">
            <Input  type="text" v-model="referenceForm.name" placeholder="Reference name" class="w-100 mb-2"
                    maxlength="30" show-word-limit @keyup.native="handleSubmit()">
                    <div slot="prepend">@</div>
            </Input>
        </FormItem>
        
    </Form>

</template>

<script>

    export default {
        props: {
            value: {
                type: String,
                default: null
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
                default:() => {}
            }
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
                        { min: 3, message: 'Reference name is too short', trigger: 'change' },
                        { max: 30, message: 'Reference name is too long', trigger: 'change' },
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
                    //  this.value exists since we are using v-model on the parent component
                    name: this.value
                }
            },
            handleSubmit(){
                //  Validate the reference name form
                this.$refs['referenceForm'].validate((valid) => 
                {   
                    //  If the validation failed
                    if (valid) {

                        //  Notify parent of the new value
                        this.$emit('input', this.referenceForm.name);

                    }else{
                        
                        //  Notify parent of the new value
                        this.$emit('input', '');
                    }
                })
            },
        },
        created(){
            //  Get the reference name form
            this.referenceForm = this.getReferenceForm();
        },
        mounted() {

            //  When the DOM Form is ready, Validate the reference name form
            this.handleSubmit();
            
        },
    }
</script>
