<template>



    <div :class="(inlineLayout ? 'd-flex' : '')">

        <span v-if="title" :class="[(inlineLayout ? '' : 'd-block mb-1'), 'font-weight-bold', 'mr-1', 'mt-1']">
            {{ title }}
        </span>

        <!-- Form -->
        <Form ref="referenceForm" :model="referenceForm" :rules="referenceFormRules" class="w-100 mb-0" @submit.native.prevent="handleSubmit()">

            <!-- Reference Name Input -->
            <FormItem prop="name" class="mb-0">
                <Input  type="text" v-model="referenceForm.name" :placeholder="placeholder" class="w-100 mb-2"
                        :size="size" maxlength="50" show-word-limit @keyup.native="handleSubmit()">
                        <div slot="prepend">@</div>
                </Input>
            </FormItem>
            
            <div v-if="hasMatchingGlobalVariable" class="bg-white p-3">
                <span class="font-weight-bold">Note: </span>
                <span class="text-info">{{ referenceForm.name }} </span>
                <span>is a Global Variable</span>

                <div>
                    <span>* </span>
                    <span v-if="matchingGlobalVariable.is_global">This variable will be saved for the next session (Unique foreach MSISDN)</span>
                    <span v-else>This variable will not be saved for the next session</span>
                </div>

                <div>
                    <span>* </span>
                    <span v-if="matchingGlobalVariable.is_constant">This is a <span class="font-italic">constant</span> variable (It's value cannot be changed)</span>
                    <span v-else>This is a <span class="font-italic">non-constant</span> variable (It's value can be changed)</span>

                </div>

                <div class="clearfix">

                    <Button type="default" class="float-right"
                            @click="handleOpenEditVariableModal()">
                            Edit Global Variable
                    </Button>
                    
                </div>

            </div>

        </Form>
        
        <!-- 
            MODAL TO EDIT GLOBAL VARIABLE
        -->
        <template v-if="isOpenEditVariableModal">

            <editVariableModal
                :version="version"
                :variable="matchingGlobalVariable"
                :index="matchingGlobalVariableIndex"
                @visibility="isOpenEditVariableModal = $event">
            </editVariableModal>

        </template>

    </div>

</template>

<script>

    import editVariableModal from './../global-variables/editVariableModal.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../mixin.js').default;

    export default {
        mixins: [customMixin],
        components: { editVariableModal },
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
            version: {
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
            placeholder: {
                type: String,
                default: 'Reference name'
            }
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
                },
                matchingGlobalVariableIndex: null,
                isOpenEditVariableModal: false
            }
        },
        computed: {
            matchingGlobalVariable(){

                //  Check if we have any Global Variable that matches the given reference name
                var matchingGlobalVariables = this.version.builder.global_variables.filter((global_variable, index) => {
                    
                    if( global_variable['name'] == this.referenceForm.name ){
                        
                        this.matchingGlobalVariableIndex = index;

                        return true;

                    }
                    
                    return false;

                });

                if( matchingGlobalVariables.length ){
                    
                    return matchingGlobalVariables[0];

                }

                return null;

            },
            hasMatchingGlobalVariable(){

                if( this.matchingGlobalVariable != null ){

                    return true;

                }

                return false;
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
            handleOpenEditVariableModal() {
                this.isOpenEditVariableModal = true;
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
