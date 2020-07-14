<template>

    <!-- Form -->
    <Form ref="headerForm" :model="headerForm" :rules="headerFormRules">

        <Row :gutter="4" class="bg-grey-light border mt-2 pt-3 px-2 pb-2">

            <Col :span="headerForm.value.code_editor_mode ? 24 : 10">

                <!-- Enter Key -->
                <FormItem prop="name" :style="{ marginTop: '-1px' }">
                    <span class="d-block font-weight-bold text-dark mb-1">Key</span>
                    <Input :class="[ headerForm.value.code_editor_mode ? 'mb-2' : '' ]"
                            type="text" v-model="headerForm.name" placeholder="price" maxlength="50">
                    </Input>
                </FormItem>

            </Col>

            <Col :span="headerForm.value.code_editor_mode ? 24 : 12">

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

            </Col>

            <Col :span="headerForm.value.code_editor_mode ? 24 : 2" class="clearfix">
                
                <!-- Remove Option Button  -->
                <Icon type="ios-trash-outline" class="float-right mt-2" size="20" @click="handleConfirmRemoveOption()"/>

            </Col>

        </Row>
        
    </Form>

</template>

<script>

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    //  Get the custom mixin files
    var globalCustomMixin = require('./../../../../../../../../../../../../../../mixin.js').default;
    var localCustomMixin = require('./localMixin.js').default;

    export default {
        components: { textOrCodeEditor },
        mixins: [globalCustomMixin, localCustomMixin],
        props: {
            index: {
                type: Number,
                default: null
            },
            header: {
                type: Object,
                default: () => {}
            },
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
            getTemplateStructure(){
                return { 
                    name: '',
                    value:{
                        text: '',
                        code_editor_text: '',
                        code_editor_mode: false
                    }
                }
            },
            getHeaderForm(){
                
                //  Set the default form details
                return Object.assign({}, this.getTemplateStructure(), this.header);

            },
            handleConfirmRemoveOption(){

                const self = this;
        
                var name = this.headers[this.index]['name'] || 'Header';

                //  Make a popup confirmation modal so that we confirm the event removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Event',
                    onOk: () => { this.handleRemoveOption() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, name),
                                '". After this header is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveOption() {
                //  Remove header from list
                this.headers.splice(this.index, 1);

                //  Header removed success message
                this.$Message.success({
                    content: 'Header removed!',
                    duration: 6
                });
            },
            handleSubmit(){
                //  Validate the header form
                this.$refs['headerForm'].validate((valid) => 
                {   
                    //  If the validation failed
                    if (valid) {
                        
                        //  Update the parent value
                        this.headers[this.index] = Object.assign({}, this.getTemplateStructure(), this.headerForm);

                    }else{
                        
                        //  Reset the parent value to nothing
                        this.headers[this.index] = Object.assign({}, this.getTemplateStructure(), {});
                    }
                });
            },
        },
        created(){
            //  Get the header form
            this.headerForm = this.getHeaderForm();
        },
        mounted() {

            //  When the DOM Form is ready, Validate the header form
            this.handleSubmit();
            
        },
    }
</script>
