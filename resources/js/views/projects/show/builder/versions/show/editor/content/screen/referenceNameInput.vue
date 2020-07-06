<template>



    <div :class="(inlineLayout ? 'd-flex' : '')">

        <span v-if="title" :class="[(inlineLayout ? '' : 'd-block mb-1'), 'font-weight-bold', 'mr-1', 'mt-1']">
            {{ title }}
        </span>

        <!-- Form -->
        <Form ref="referenceForm" :model="referenceForm" :rules="referenceFormRules" class="w-100 mb-0">

            <!-- Reference Name Input -->
            <FormItem prop="name" class="mb-0">
                <Input  type="text" v-model="referenceForm.name" placeholder="Reference name" class="w-100 mb-2"
                        :size="size" maxlength="50" show-word-limit @keyup.native="handleSubmit()">
                        <div slot="prepend">@</div>
                </Input>
            </FormItem>
            
        </Form>
        
    </div>

</template>

<script>

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../mixin.js').default;

    export default {
        mixins: [customMixin],
        props: {
            referenceNames: {
                type: Array,
                default: () => []
            },
            index: {
                type: Number,
                default: null
            },
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
            },
            size: {
                type: String,
                default: 'default'
            },
            isRequired: {
                type: Boolean,
                default: true
            },
            title: {
                type: String,
                default: null
            },
            inlineLayout: {
                type: Boolean,
                default: true
            },
        },
        data(){

            //  Custom validation to detect matching reference names
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if reference names with the same name exist
                var similarNamesExist = this.referenceNames.filter( (reference_name, index) => { 
                
                    //  Skip checking the current reference name
                    if( this.index == index ){
                        return false;
                    }

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

            return {
                referenceForm: null,
                referenceFormRules: {
                    name: [
                        { required: this.isRequired, message: 'Reference name is required', trigger: 'change' },
                        { min: 3, message: 'Reference name is too short', trigger: 'change' },
                        { max: 50, message: 'Reference name is too long', trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' },
                        { validator: this.getValidVariableNameValidator(), trigger: 'change' }
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
