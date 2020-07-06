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
            title="Add Status Code Handler"
            @on-visible-change="detectClose">

            <!-- Status Codes -->
            <Row :gutter="20">

                <Col :span="8" v-for="(statusType, key) in statusTypes" :key="key" class="mb-2">
                    
                    <Card @click.native="addStatusCodeHandler(statusType)" :padding="0"
                            :class="[isGoodStatus(statusType) ? 'bg-success' : 'bg-danger', 'cursor-pointer']">
                        
                        <div style="padding: 30px;">
                            
                            <!-- Status Name -->
                            <p class="text-center text-white">{{ statusType }}</p>

                        </div>

                    </Card>

                </Col>

            </Row>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    //  Get the custom mixin files
    import modalMixin from './../../../../../../../../../../../../../../components/_mixins/modal/main.vue';
    var localCustomMixin = require('./localMixin.js').default;

    export default {
        mixins: [modalMixin, localCustomMixin],
        props: {
            statusCodeHandles: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {

            }
        },
        methods: {
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            addStatusCodeHandler(statusType){

                var statusCodeHandle = this.createStatusCodeHandle( statusType );
                
                //  Make sure the new handle does not conflict with any other handles
                for(var x=0; x < this.statusCodeHandles.length; x++){

                    if( this.statusCodeHandles[x].status == statusCodeHandle.status ){

                        this.$Message.warning({
                            content: 'Status code handler already exists!',
                            duration: 6
                        });

                        //  Stop - This status code handle already exists
                        return null;

                    }

                }

                //  Add the given status code handle
                this.statusCodeHandles.push( statusCodeHandle );

                this.$Message.success({
                    content: 'Status code handler added!',
                    duration: 6
                });

                //  Notify the parent component of the selected status handle
                this.$emit('createdStatusHandle', statusCodeHandle);

                //  Close the modal
                this.closeModal();
            },
            createStatusCodeHandle( statusType ){

                return {
                    status: statusType,
                    reference_name: 'response',              //  e.g "response", "api_response", "api_data",
                    attributes: [
                        {
                            name: '',
                            value: ''
                        }
                    ],
                    on_handle: {
                        selected_type: 'use_custom_msg',   //  do_nothing, use_custom_msg
                        use_custom_msg: {
                            text: '',
                            code_editor_text: '',
                            code_editor_mode: false
                        }
                    }
                };

            }
        }
    }
</script>