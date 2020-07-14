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
            <Divider orientation="left" class="font-weight-bold">Screen Details</Divider>

            <Alert v-if="isCloning" show-icon>Cloning "<span class="font-weight-bold">{{ screen.name }}</span>"</Alert>

            <!-- Form -->
            <Form ref="screenForm" :model="screenForm" :rules="screenFormRules">

                <!-- Enter Name -->
                <FormItem prop="name">
                    <Input  type="text" v-model="screenForm.name" placeholder="Home" maxlength="50" 
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

    import modalMixin from './../../../../../../../../components/_mixins/modal/main.vue';

    //  Get the custom mixin file
    var customMixin = require('./../../../../../../../../mixin.js').default;

    export default {
        mixins: [modalMixin, customMixin],
        props: {
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

                //  Check if screens with the same name exist
                var similarNamesExist = this.builder.screens.filter( (screen) => { 
                    //  If the given value matches the screen name
                    return (value == screen.name);
                }).length;

                //  If screens with a similar name exist
                if (similarNamesExist) {
                    callback(new Error('This screen name is already in use'));
                } else {
                    callback();
                }
            };

            return {
                screenForm: null,
                screenFormRules: {
                    name: [
                        { required: true, message: 'Please enter your screen name', trigger: 'blur' },
                        { min: 3, message: 'Screen name is too short', trigger: 'change' },
                        { max: 50, message: 'Screen name is too long', trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' }
                    ],
                }
            }
        },
        computed: {
            isCloning(){
                //  If we have a screen provided, then we are cloning
                return this.screen ? true : false;
            },
            modalTitle(){
                return this.isCloning ? 'Clone Screen' : 'Add Screen';
            },
            modalOkBtnText(){
                return this.isCloning ? 'Clone' : 'Add Screen'
            },
            totalScreens(){
                return this.builder.screens.length;
            }
        },
        methods: {

            getScreenForm(){

                //  Set the default form details
                return { 
                    name: '' 
                }

            },
            handleSubmit(){
                //  Validate the screen form
                this.$refs['screenForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        //  Add the screen
                        this.addScreen();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your screen yet',
                            duration: 6
                        });
                    }
                })
            },
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            addScreen(){

                //  If we are adding a cloned screen
                if( this.isCloning ){

                    this.handleAddCloneScreen();

                //  If we are adding a new screen
                }else{

                    this.handleAddNewScreen();

                }

                this.closeModal();
            },
            handleAddCloneScreen(){

                //  Clone the screen
                var clonedScreen = _.cloneDeep( this.screen );

                //  Generate the screen id
                var id = this.generateScreenId();

                //  Update the screen id and name
                clonedScreen.id = id;
                clonedScreen.name = this.screenForm.name;

                //  Turn off the first display screen attribute
                clonedScreen.first_display_screen = false;

                //  Add the cloned screen to the rest of the other screens
                this.builder.screens.push(clonedScreen);

                var newScreenIndex = this.totalScreens - 1;
                
                //  Set the cloned screen as the current active screen
                this.$emit('selectedScreen', newScreenIndex);

                this.$Message.success({
                    content: 'Screen cloned!',
                    duration: 6
                });
            },
            handleAddNewScreen(){

                //  Generate the screen id
                var id = this.generateScreenId();

                //  Get the screen name
                var screenName = this.screenForm.name;

                /** Determine whether this must be the first display screen by default.
                 *  Generally if we don't already have any screen assigned as the first
                 *  display screen, then we make this screen the first display screen by
                 *  default.
                 */
                var firstDisplayScreen = !((this.builder.screens || []).filter( (screen) => { 
                    return screen.first_display_screen == true;
                }).length ? true : false);

                //  Build the screen template
                var screenTemplate = {
                        id: id,
                        name: screenName, 
                        repeat: {
                            active: {
                                text: false,
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            selected_type: 'repeat_on_number',  //  repeat_on_number, repeat_on_items, custom_repeat
                            repeat_on_number: {
                                value:{
                                    text: '3',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                },
                                total_loops_reference_name: 'total_items',
                                loop_index_reference_name: 'loop_index',
                                loop_number_reference_name: 'loop_number',
                                is_first_loop_reference_name: 'is_first_loop',
                                is_last_loop_reference_name: 'is_last_loop',
                                on_no_loop: {
                                    selected_type: 'do_nothing',            //  do_nothing, link
                                    link: {
                                        text: '',
                                        code_editor_text: '',
                                        code_editor_mode: false
                                    }
                                },
                                after_last_loop: {
                                    selected_type: 'do_nothing',            //  do_nothing, link
                                    link: {
                                        text: '',
                                        code_editor_text: '',
                                        code_editor_mode: false
                                    }
                                }
                            },
                            repeat_on_items: {
                                group_reference: {
                                    text: '{{ items }}',
                                    code_editor_text: '',
                                    code_editor_mode: false
                                }, 
                                item_reference_name: 'item',
                                total_loops_reference_name: 'total_items',
                                loop_index_reference_name: 'item_index',
                                loop_number_reference_name: 'item_number',
                                is_first_loop_reference_name: 'is_first_item',
                                is_last_loop_reference_name: 'is_last_item',
                                on_no_loop: {
                                    selected_type: 'do_nothing',            //  do_nothing, link
                                    link: {
                                        text: '',
                                        code_editor_text: '',
                                        code_editor_mode: false
                                    }
                                },
                                after_last_loop: {
                                    selected_type: 'do_nothing',            //  do_nothing, link
                                    link: {
                                        text: '',
                                        code_editor_text: '',
                                        code_editor_mode: false
                                    }
                                }
                            },
                            events: {
                                before_repeat: [],
                                after_repeat: []
                            }
                        },
                        first_display_screen: firstDisplayScreen,
                        conditional_displays: {
                            active: false,
                            code: null
                        }, 
                        displays: [],
                        markers: []
                    };

                //  Add the screen to the screen tree
                this.builder.screens.push( screenTemplate );

                var newScreenIndex = this.totalScreens - 1;
                
                //  Set the cloned screen as the current active screen
                this.$emit('selectedScreen', newScreenIndex);

                this.$Message.success({
                    content: 'Screen added!',
                    duration: 6
                });

            },
            generateScreenId(){
                return 'screen_' + Date.now();
            }
        },
        created(){
            this.screenForm = this.getScreenForm();
        }
    }
</script>