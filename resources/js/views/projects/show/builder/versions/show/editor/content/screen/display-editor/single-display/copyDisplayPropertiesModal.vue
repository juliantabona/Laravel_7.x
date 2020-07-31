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
            title="Display Properties"
            @on-visible-change="detectClose">

            <!-- Status Codes -->
            <Row :gutter="20">

                <Col :span="8" v-for="(property, key) in properties" :key="key" class="mb-2">
                    
                    <Card @click.native="handleSelection(property)" :padding="0" :class="['cursor-pointer']">
                        
                        <div style="padding: 30px;">
                            
                            <!-- Status Name -->
                            <p class="text-center">{{ property.name }}</p>
                            
                            <Icon v-if="isSelected(property)"
                                  type="ios-checkmark-circle" class="text-success" :size="30"
                                  :style="{ bottom: '5px', right: '5px', position: 'absolute', fontSize: '30px' }" />
                        </div>

                    </Card>

                </Col>

            </Row>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                    <Button @click.native="copyProperties()" type="primary" class="float-right mr-2">Copy Properties</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    //  Get the custom mixin files
    import modalMixin from './../../../../../../../../../../../components/_mixins/modal/main.vue';

    export default {
        mixins: [modalMixin],
        props: {
            display: {
                type: Object,
                default: null
            },
        },
        data(){
            return {
                selected_properties: [],
                properties: [
                    {
                        name: 'Instruction',
                        value: 'description'
                    },
                    {
                        name: 'Action',
                        value: 'action'
                    },
                    {
                        name: 'Events',
                        value: 'events'
                    }
                ]
            }
        },
        methods: {
            /** Note the closeModal() method is imported from the
             *  modalMixin file. It handles the closing process 
             *  of the modal
             */
            copyProperties(){

                var display_properties = {};
                console.log('this.display');
                console.log(this.display);

                //  If the property was already selected then remove it
                for (let index = 0; index < this.selected_properties.length; index++) {

                    var property = this.selected_properties[index]['value'];

                    var property_value = this.display.content[property];

                    display_properties[ property ] = property_value;

                }

                //  Store the display properties in the local storage
                window.localStorage.setItem('display_properties', JSON.stringify(display_properties));

                this.$Message.success({
                    content: 'Display properties copied!',
                    duration: 6
                });

                //  Close the modal
                this.closeModal();

            },
            handleSelection(property){

                //  If the property was already selected then remove it
                if( this.isSelected(property) ){

                    var index = this.selectedPropertyIndex(property);

                    //  Remove the property if it already exists
                    this.selected_properties.splice(index, 1);

                    return null;

                }

                //  If the property was not already selected then add it 
                this.selected_properties.push(property);

            },
            isSelected(property){

                //  If the property was already selected then remove it
                for (let index = 0; index < this.selected_properties.length; index++) {
                    
                    if( this.selected_properties[index]['value'] == property.value ){
                        
                        return true;

                    }

                }
                
                return false;

            },
            selectedPropertyIndex(property){

                //  If the property was already selected then remove it
                for (let index = 0; index < this.selected_properties.length; index++) {
                    
                    if( this.selected_properties[index]['value'] == property.value ){
                        
                        return index;

                    }

                }
                
                return null;

            }
        }
    }
</script>