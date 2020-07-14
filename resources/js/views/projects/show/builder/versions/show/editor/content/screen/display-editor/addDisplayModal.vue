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
            :title="modalTitle"
            v-model="modalVisible"
            @on-visible-change="detectClose">
                        
            <!-- Heading -->
            <Divider orientation="left" class="font-weight-bold">Display Details</Divider>

            <Alert v-if="isCloning" show-icon>Cloning "<span class="font-weight-bold">{{ display.name }}</span>"</Alert>

            <!-- Form -->
            <Form ref="displayForm" :model="displayForm" :rules="displayFormRules">

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="displayForm.name" placeholder="Welcome" maxlength="50" 
                            show-word-limit @keyup.enter.native="handleSubmit()" v-focus="'input'">
                            <span slot="prepend">Name</span>
                    </Input>
                </FormItem>
                
            </Form>

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

    import modalMixin from './../../../../../../../../../../components/_mixins/modal/main.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
        props: {
            display: {
                type: Object,
                default: null
            },
            screen: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default:() => {}
            },
        },
        data(){

            //  Custom validation to detect non matching passwords
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if displays with the same name exist
                var similarNamesExist = this.screen.displays.filter( (display) => { 
                    //  If the given value matches the display name
                    return (value == display.name);
                }).length;

                //  If displays with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This display name is already in use'));
                } else {
                    callback();
                }
            };

            return {
                displayForm: null,
                displayFormRules: {
                    name: [
                        { required: true, message: 'Please enter your display name', trigger: 'blur' },
                        { min: 3, message: 'Display name is too short', trigger: 'change' },
                        { max: 50, message: 'Display name is too long', trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' }
                    ],
                }
            }
        },
        computed: {
            isCloning(){
                //  If we have a display provided, then we are cloning
                return this.display ? true : false;
            },
            modalTitle(){
                return this.isCloning ? 'Clone Display' : 'Add Display';
            },
            modalOkBtnText(){
                return this.isCloning ? 'Clone' : 'Add Display'
            }
        },
        methods: {

            getDisplayForm(){

                //  Set the default form details
                return { 
                    name: '' 
                }

            },
            handleSubmit(){
                //  Validate the display form
                this.$refs['displayForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {
                        
                        //  Add the display
                        this.addDisplay();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your display yet',
                            duration: 6
                        });
                    }
                })
            },
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            addDisplay(){

                //  If we are adding a cloned display
                if( this.isCloning ){

                    this.handleAddCloneDisplay();

                //  If we are adding a new display
                }else{

                    this.handleAddNewDisplay();

                }

                this.closeModal();
            },
            handleAddCloneDisplay(){

                //  Clone the display
                var clonedDisplay = _.cloneDeep( this.display );

                //  Generate the display id
                var id = this.generateDisplayId();

                //  Update the display id and name
                clonedDisplay.id = id;
                clonedDisplay.name = this.displayForm.name;

                //  Turn off the first display display attribute
                clonedDisplay.first_display = false;

                //  Add the cloned display to the rest of the other displays
                this.screen.displays.push(clonedDisplay);

                this.$Message.success({
                    content: 'Display cloned!',
                    duration: 6
                });

            },
            handleAddNewDisplay(){

                //  Generate the display id
                var id = this.generateDisplayId();

                //  Get the display name
                var displayName = this.displayForm.name;

                /** Determine whether this must be the first display by default.
                 *  Generally if we don't already have any display assigned as the
                 *  first display, then we make this display the first display by default.
                 */
                var firstDisplay = !((this.screen.displays || []).filter( (display) => { 
                    return display.first_display == true;
                }).length ? true : false);

                //  Build the display template
                var displayTemplate = {
                        id: id,
                        name: displayName, 
                        first_display: firstDisplay,
                        content: this.getDisplayContentTemplate(),
                        hexColor: '#CECECE'
                    };

                //  Add the display to the displays
                this.screen.displays.push( displayTemplate );

                this.$Message.success({
                    content: 'Display added!',
                    duration: 6
                });

            },
            getDisplayContentTemplate(){
                
                return  {

                    //  Display description properties
                    description: {
                        text: '',
                        code_editor_text: '',
                        code_editor_mode: false
                    },

                    //  Display action properties
                    action: {
                        selected_type: 'no_action',  //  no_action, input_value, select_option
                        input_value: {
                            selected_type: 'single_value_input',    //  single_value_input, multi_value_input
                            single_value_input: {
                                reference_name: null,
                                link:{
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }
                            },
                            multi_value_input: {
                                separator: 'spaces',
                                reference_names: ['first_name', 'last_name'],
                                link:{
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }
                            }
                        },
                        select_option: {
                            selected_type: 'static_options',    //  static_options, dynamic_options, code_editor_options
                            static_options: {
                                options: [
                                    /*  Example option
                                    {
                                        name: {
                                            text: '1. My Option',
                                            code_editor_text: '',
                                            code_editor_mode: false
                                        },
                                        active: {
                                            text: true,
                                            code_editor_text: '',
                                            code_editor_mode: false
                                        },
                                        value: {
                                            text: '',
                                            code_editor_text: '',
                                            code_editor_mode: false
                                        },
                                        input: {
                                            text: '1',
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
                                    }
                                    */
                                ],
                                reference_name: '',
                                no_results_message: {
                                    text: 'No options found',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                incorrect_option_selected_message: {
                                    text: 'You selected an incorrect option. Go back and try again',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }
                            }, 
                            dynamic_options: {
                                group_reference: {
                                    text: '{{ items }}', 
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                template_reference_name: 'item',
                                template_display_name: {
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                template_value: {
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                reference_name: 'selected_item',
                                no_results_message: {
                                    text: 'No items found',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                incorrect_option_selected_message: {
                                    text: 'You selected an incorrect option. Go back and try again',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                link:{
                                    text: '',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }
                            },
                            code_editor_options: {
                                code_editor_text: null,
                                reference_name: 'selected_item',
                                no_results_message: {
                                    text: 'No items found',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                incorrect_option_selected_message: {
                                    text: 'You selected an incorrect option. Go back and try again',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }
                            }
                        }
                    },

                    //  Repeat navigation properties
                    screen_repeat_navigation: {
                        forward_navigation: [],
                        backward_navigation: []
                    },

                    //  Event settings
                    events: {
                        before_reply: [],
                        after_reply: []
                    },
                    
                    //  Pagination settings
                    pagination: {
                        active: {
                            text: true,
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        content_target: {
                            selected_type: 'both'         //  instruction, action, both
                        },
                        paginate_by_line_breaks: true,

                        slice: {
                            separation_type: 'words',     //  characters, words
                            start: {
                                text: '0',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            end: {
                                text: '160',
                                code_editor_text: '',
                                code_editor_mode: false
                            }
                        },
                        scroll_down: {
                            name: {
                                text: '99. Next',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            input: {
                                text: '99',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            visible: true
                        },
                        scroll_up: {
                            name: {
                                text: '88. Prev',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            input: {
                                text: '88',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            visible: true
                        },
                        trailing_end: {
                            text: '...',
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        break_line_before_trail: false,
                        break_line_after_trail: false,
                    }
                }
            },
            generateDisplayId(){
                return 'display_' + Date.now();
            }
        },
        created(){
            this.displayForm = this.getDisplayForm();
        }
    }
</script>