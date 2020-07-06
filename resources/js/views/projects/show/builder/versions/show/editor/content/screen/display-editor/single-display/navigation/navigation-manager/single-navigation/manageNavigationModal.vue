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
            <Form ref="navigationForm" :model="navigationForm" :rules="navigationFormRules">

                <Row :gutter="12">

                    <!-- Navigation Name -->
                    <Col :span="navigationForm.active.code_editor_mode ? 24 : 16">

                        <!-- Enter Name -->
                        <FormItem prop="name" class="mb-2">
                            <Input  type="text" v-model="navigationForm.name" placeholder="Navigation name" maxlength="50" show-word-limit>
                                    <span slot="prepend">Name</span>
                            </Input>
                        </FormItem>

                    </Col>

                    <!-- Navigation Active Status -->
                    <Col :span="navigationForm.active.code_editor_mode ? 24 : 8">
                    
                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateCheckbox v-model="navigationForm.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

                    </Col>

                    <!-- Navigation Type -->
                    <Col :span="24" class="d-flex bg-grey-light py-2 px-3 mt-3 mb-3">
                    
                        <span class="d-block font-weight-bold text-dark mt-2 mr-2">Type: </span>
                        
                        <Select v-model="navigationForm.selected_type" class="w-50" placeholder="Type">
                            
                            <Option v-for="(action, key) in navigationTypes" :key="key" class="mb-2"
                                    :value="action.value" :label="action.name">
                            </Option>

                        </Select>

                    </Col>  

                    <!-- Custom Navigation Settings  -->
                    <Col v-if="navigationForm.selected_type == 'custom'" key="custom_navigation" :span="24" class="bg-grey-light p-3">
                        
                        <!-- Custom Navigation Inputs -->
                        <FormItem prop="inputs" class="mb-2">
                            <textOrCodeEditor
                                size="small"
                                title="Input(s)"
                                placeholder="1, 2, 3"
                                sampleCodeTemplate="ussd_service_select_navigation_input_sample_code"
                                :value="navigationForm.custom.inputs">
                            </textOrCodeEditor>
                        </FormItem>

                        <!-- Navigation Step -->
                        <textOrCodeEditor
                            size="small"
                            title="Step"
                            placeholder="2"
                            sampleCodeTemplate="ussd_service_select_navigation_input_sample_code"
                            :value="navigationForm.custom.step">
                        </textOrCodeEditor>

                    </Col>   

                    <!-- Regex Navigation Settings  -->
                    <Col v-if="navigationForm.selected_type == 'regex'" key="regex_navigation" :span="24" class="bg-grey-light p-3">
                        
                        <!-- Regex Navigation Rule -->
                        <FormItem prop="regex_pattern" class="mb-2">
                            <textOrCodeEditor
                                size="small"
                                title="Regex Rule"
                                placeholder="1, 2, 3"
                                :value="navigationForm.regex.rule"
                                sampleCodeTemplate="ussd_service_select_navigation_input_sample_code">
                            </textOrCodeEditor>
                        </FormItem>

                        <!-- Navigation Step -->
                        <textOrCodeEditor
                            size="small"
                            title="Step"
                            placeholder="2"
                            :value="navigationForm.regex.step"
                            sampleCodeTemplate="ussd_service_select_navigation_input_sample_code">
                        </textOrCodeEditor>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Comment -->
                        <commentInput v-model="navigationForm.comment" class="mt-2"></commentInput>

                    </Col>

                </Row>

            </Form>

            <div class="border-top pt-3 mt-3">

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="navigationForm.hexColor" recommend></ColorPicker>
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

    import modalMixin from './../../../../../../../../../../../../../../components/_mixins/modal/main.vue';
    import activeStateCheckbox from './../../../../../activeStateCheckbox.vue';
    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import commentInput from './../../../../../commentInput.vue';
    var localCustomMixin = require('./../localMixin.js').default;

    export default {
        mixins: [modalMixin, localCustomMixin],
        components: { activeStateCheckbox, textOrCodeEditor, commentInput },
        props: {
            index: {
                type: Number,
                default: null
            },
            navigation: {
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
            navigations: {
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

            //  Custom validation to detect matching names
            const uniqueNameValidator = (rule, value, callback) => {

                //  Check if navigations with the same input exist
                var similarNamesExist = this.navigations.filter( (navigation, index) => {

                    //  If we are editing
                    if( this.isEditing ){

                        //  Skip checking the current navigation
                        if( this.index == index ){
                            return false;
                        }

                    }

                    if( this.navigationForm.name == navigation.name){

                        return true
                        
                    }

                    return false;
                    
                
                }).length;

                //  If navigations with similar names exist
                if (similarNamesExist) {
                    callback(new Error('This navigation name is already in use'));
                } else {
                    callback();
                }
            };

            //  Custom validation to detect matching inputs or regex patterns
            const uniqueInputAndRegexPatternValidator = (rule, value, callback) => {

                //  Check if navigations with the same input exist
                var similarInputsExist = this.navigations.filter( (navigation, index) => {

                    //  If we are editing
                    if( this.isEditing ){

                        //  Skip checking the current navigation
                        if( this.index == index ){
                            return false;
                        }

                    }

                    if( this.navigationForm.selected_type == 'custom' && navigation.selected_type == 'custom'){

                        //  If the navigation custom input is not using code editor mode
                        if( !this.navigationForm.custom.inputs.code_editor_mode && !navigation.custom.inputs.code_editor_mode ){
                            
                            //  If the given custom inputs matches the navigation custom inputs
                            return (this.navigationForm.custom.inputs.text == navigation.custom.inputs.text);

                        }
                        
                    }else if( this.navigationForm.selected_type == 'regex' && navigation.selected_type == 'regex'){
                        
                        //  If the navigation regex pattern is not using code editor mode
                        if( !this.navigationForm.regex.rule.code_editor_mode && !navigation.regex.rule.code_editor_mode ){
                            
                            //  If the given regex pattern matches the navigation regex pattern
                            return (this.navigationForm.regex.rule.text == navigation.regex.rule.text);

                        }

                    }

                    return false;
                    
                
                }).length;

                //  If navigations with similar inputs or regex patterns exist
                if (similarInputsExist && rule.field == 'inputs') {
                    callback(new Error('This navigation input(s) is already in use'));
                }else if (similarInputsExist && rule.field == 'regex_pattern') {
                    callback(new Error('This navigation regex pattern is already in use'));
                } else {
                    callback();
                }
            };

            return {
                /** Note: The "navigationTypes" property is located
                 *  within the "localCustomMixin"
                 */
                navigationForm: null,
                navigationFormRules: { 
                    name: [
                        { required: true, message: 'Please enter your navigation name', trigger: 'blur' },
                        { min: 3, message: 'Navigation name is too short', trigger: 'change' },
                        { max: 50, message: 'Navigation name is too long', trigger: 'change' },
                        { validator: uniqueNameValidator, trigger: 'change' }
                    ],
                    inputs: [
                        { validator: uniqueInputAndRegexPatternValidator, trigger: 'change' }
                    ],
                    regex_pattern: [
                        { validator: uniqueInputAndRegexPatternValidator, trigger: 'change' }
                    ]
                }
            }
        },
        computed: {
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Navigation';

                }else if( this.isCloning ){

                    return 'Clone Navigation';
                
                }else{

                    return 'Add Navigation';

                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }else{

                    return 'Add Navigation';

                }

            },
            getNavigationNumber(){
                /**
                 *  Returns the navigation number. We use this as we list the navigations.
                 *  It works like a counter.
                 */
                return (this.index != null ? this.index + 1 : '');
            },    
            totalNavigations(){
                return this.navigations.length;
            },
        },
        methods: {
            getNavigationForm(){
                 
                var overides = {};

                //  If we are editing or cloning
                if( this.isEditing || this.isCloning ){
                    
                    //  Set the overide data
                    overides = this.navigation;

                }

                var navigation_number = (this.totalNavigations + 1).toString();
                
                var navigation = Object.assign({},
                    //  Set the default form details
                    {
                        id: this.generateNavigationId(),
                        name: 'Navigation ' + navigation_number,
                        active: {
                            text: true,
                            code_editor_text: '',
                            code_editor_mode: false
                        },
                        selected_type: 'custom',  //  none, any, only_numbers, custom, regex
                        custom: {
                            inputs: {
                                text: '1, 2, 3',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            step: {
                                text: '1',
                                code_editor_text: '',
                                code_editor_mode: false
                            }
                        },
                        regex: {
                            rule: {
                                text: '/[a-zA-Z]+/',
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                            step: {
                                text: '1',
                                code_editor_text: '',
                                code_editor_mode: false
                            }
                        },
                        hexColor: '#CECECE',
                        comment: ''
                    //  Overide the default form details with the provided navigation details
                    }, overides);

                return _.cloneDeep( navigation );

            },
            handleSubmit(){
                
                //  Validate the navigation form
                this.$refs['navigationForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditNavigation();

                        }else if( this.isCloning ){
                        
                            this.handleCloneNavigation();

                        }else{

                            //  Add the navigation
                            this.handleAddNewNavigation();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your navigation yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditNavigation(){

                //  Update the navigation
                this.$set(this.navigations, this.index, this.navigationForm);

                this.$Message.success({
                    content: 'Navigation updated!',
                    duration: 6
                });

            },
            handleCloneNavigation(){

                //  Update the navigation id
                this.navigationForm.id = this.generateNavigationId();

                //  Add the cloned navigation to the rest of the other navigations
                this.navigations.push(this.navigationForm);

                this.$Message.success({
                    content: 'Navigation cloned!',
                    duration: 6
                });

            },
            handleAddNewNavigation(){

                //  Add the new navigation to the rest of the other navigations
                var newIndex = this.navigations.length;

                this.$set(this.navigations, newIndex, this.navigationForm);

                this.$Message.success({
                    content: 'Navigation added!',
                    duration: 6
                });

            },
            generateNavigationId(){
                return 'navigation_' + Date.now();
            }
        },
        created(){
            this.navigationForm = this.getNavigationForm();
        }
    }
</script>