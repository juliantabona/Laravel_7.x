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
            <Form ref="staticOptionForm" :model="staticOptionForm" :rules="staticOptionFormRules">

                <!-- Enter Name -->
                <FormItem prop="name" class="mb-1">

                    <textOrCodeEditor
                        size="small"
                        title="Display Name"
                        :placeholder="'1. My Messages ({{ messages.count }})'"
                        sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"
                        :value="staticOptionForm.name">
                    </textOrCodeEditor>

                </FormItem>

                <!-- Enter Value -->
                <FormItem prop="value" class="mb-1">

                    <textOrCodeEditor
                        size="small"
                        title="Value"
                        :placeholder="'{{ messages }}'"
                        sampleCodeTemplate="ussd_service_select_option_value_sample_code"
                        :value="staticOptionForm.value">
                    </textOrCodeEditor>

                </FormItem>

                <!-- Enter Input -->
                <FormItem prop="input" class="mb-1">

                    <textOrCodeEditor
                        size="small"
                        title="Input"
                        placeholder="1"
                        sampleCodeTemplate="ussd_service_select_option_input_sample_code"
                        :value="staticOptionForm.input">
                    </textOrCodeEditor>

                    Validate Input: {{ staticOptionForm.input }}

                </FormItem>

                <!-- Enter Top Separator -->
                <FormItem prop="top_separator" class="mb-1">

                    <textOrCodeEditor
                        size="small"
                        placeholder="---"
                        title="Top Separator"
                        sampleCodeTemplate="ussd_service_select_option_top_separator_sample_code"
                        :value="staticOptionForm.separator.top">
                    </textOrCodeEditor>

                </FormItem>

                <!-- Enter Bottom Separator -->
                <FormItem prop="bottom_separator" class="mb-1">

                    <textOrCodeEditor
                        size="small"
                        placeholder="---"
                        title="Bottom Separator"
                        sampleCodeTemplate="ussd_service_select_option_bottom_separator_sample_code"
                        :value="staticOptionForm.separator.bottom">
                    </textOrCodeEditor>

                </FormItem>

                <!-- Select Screen / Display Link -->
                <screenAndDisplaySelector 
                    :link="staticOptionForm.link" class="mt-2"
                    :builder="builder" :screen="screen" :display="display">
                </screenAndDisplaySelector>

                <!-- Enter Comment -->
                <commentInput v-model="staticOptionForm.comment" class="mt-2"></commentInput>

            </Form>

            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="staticOptionForm.hexColor" recommend></ColorPicker>
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

    import modalMixin from './../../../../../../../../../../../../../../../components/_mixins/modal/main.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../../../../../../../mixin.js').default;

    import screenAndDisplaySelector from './../../../../../../screenAndDisplaySelector.vue';
    import textOrCodeEditor from './../../../../../../textOrCodeEditor.vue';
    import commentInput from './../../../../../../commentInput.vue';

    export default {
        mixins: [modalMixin, customMixin],
        components: { screenAndDisplaySelector, textOrCodeEditor, commentInput },
        props: {

            index: {
                type: Number,
                default: null
            },
            option: {
                type: Object,
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
                default: null
            },
            options: {
                type: Array,
                default: () => []
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

            //  Custom validation to detect matching inputs
            const uniqueInputValidator = (rule, value, callback) => {
                
                console.log('uniqueInputValidator');

                //  Check if static options with the same input exist
                var similarInputsExist = this.options.filter( (option, index) => {

                    console.log('uniqueInputValidator stage 1');

                    //  If we are editing
                    if( this.isEditing ){

                        //  Skip checking the current static option
                        if( this.index == index ){
                            return false;
                        }

                    }

                    console.log('uniqueInputValidator stage 2');

                    //  If the option is not using code editor mode
                    if( !option.input.code_editor_mode ){

                        console.log('uniqueInputValidator stage 3');
                        
                        //  If the given value matches the static option input
                        return (value == option.input.text);

                    }

                    console.log('uniqueInputValidator stage 4');

                    return false;
                    
                
                }).length;

                //  If static options with a similar name exist
                if (similarInputsExist) {
                    callback(new Error('This static option name is already in use'));
                } else {
                    callback();
                }
            };

            return {
                staticOptionForm: null,
                staticOptionFormRules: { 
                    input: [
                        { validator: uniqueInputValidator, trigger: 'blur' }
                    ]
                }
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Static Option';

                }else if( this.isCloning ){

                    return 'Clone Static Option';
                
                }else{

                    return 'Add Static Option';

                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }else{

                    return 'Add Option';

                }

            },
            getStaticOptionNumber(){
                /**
                 *  Returns the static option number. We use this as we list the static options.
                 *  It works like a counter.
                 */
                return (this.index != null ? this.index + 1 : '');
            },    
            totalOptions(){
                return this.options.length;
            },
        },
        methods: {
            getStaticOptionForm(){
                 
                var overides = {};

                //  If we are editing or cloning
                if( this.isEditing || this.isCloning ){
                    
                    //  Set the overide data
                    overides = this.option;

                }

                var option_number = (this.totalOptions + 1).toString();
                
                return _.cloneDeep( 
                    
                    Object.assign({},
                    //  Set the default form details
                    {
                        id: this.generateStaticOptionId(),
                        name: {
                            text: option_number + '. My Option',
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        active: {
                            selected_type: 'yes',      //  yes, no, conditional
                            conditional: {
                                code_editor_text: '',
                            }
                        },
                        value: {
                            text: '',
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        input: {
                            text: option_number,
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        separator: {
                            top: {
                                text: '',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            bottom: {
                                text: '',
                                code_editor_text: '',
                                code_editor_mode: false
                            }
                        },
                        link:{
                            text: '',
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        hexColor: '#CECECE',
                        comment: ''
                    //  Overide the default form details with the provided project details
                    }, overides)
                );

            },
            handleSubmit(){
                
                //  Validate the static option form
                this.$refs['staticOptionForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        console.log('Stage 1');


                        if( this.isEditing ){
                            console.log('Stage 2.1');
                        
                            this.handleEditStaticOption();

                        }else if( this.isCloning ){
                            console.log('Stage 2.2');
                        
                            this.handleCloneStaticOption();

                        }else{
                            console.log('Stage 2.3');

                            //  Add the static option
                            this.handleAddNewStaticOption();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your static option yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditStaticOption(){

                //  Update the option
                this.$set(this.options, this.index, this.staticOptionForm);

                this.$Message.success({
                    content: 'Static option updated!',
                    duration: 6
                });

            },
            handleCloneStaticOption(){
                
                console.log('Clone');
                console.log(this.staticOptionForm);

                //  Update the static option id
                this.staticOptionForm.id = this.generateStaticOptionId();

                //  Add the cloned static option to the rest of the other static options
                this.options.push(this.staticOptionForm);

                this.$Message.success({
                    content: 'Static option cloned!',
                    duration: 6
                });

            },
            handleAddNewStaticOption(){

                //  Add the new static option to the rest of the other static options
                var newIndex = this.options.length;

                this.$set(this.options, newIndex, this.staticOptionForm);

                this.$Message.success({
                    content: 'Static option added!',
                    duration: 6
                });

            },
            generateStaticOptionId(){
                return 'static_option_' + Date.now();
            }
        },
        created(){
            this.staticOptionForm = this.getStaticOptionForm();
        }
    }
</script>