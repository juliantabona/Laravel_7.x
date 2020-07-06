<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Key/Value Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <span class="single-draggable-item-title d-block font-weight-bold cut-text">{{ keyValue.key }}</span>

                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <List slot="content" size="small">

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Active: </span>
                                <span v-if="keyValue.active.code_editor_mode">

                                    <Icon type="ios-code" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text">{{ keyValue.active.text ? 'Yes' : 'No' }}</span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Key: </span>
                                <span class="cut-text">{{ keyValue.key }}</span>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Value: </span>
                                <span v-if="keyValue.value.code_editor_mode">

                                    <Icon type="ios-code" class="mr-1" size="20" />
                                    <span>Custom Code</span>

                                </span>
                                <template v-else>
                                    <span class="cut-text" v-html="keyValue.value.text"></span>
                                </template>
                            </ListItem>

                            <ListItem class="p-0">
                                <span class="font-weight-bold mr-1">Default: </span>
                                <template v-if="keyValue.on_empty_value.default.selected_type == 'custom'">
                                    <span v-if="keyValue.on_empty_value.default.custom.code_editor_mode">

                                        <Icon type="ios-code" class="mr-1" size="20" />
                                        <span>Custom Code</span>

                                    </span>
                                    <template v-else>
                                        <span class="cut-text" v-html="keyValue.on_empty_value.default.custom.text"></span>
                                    </template>
                                </template>
                                <span v-else class="cut-text">{{ defaultType }}</span>
                            </ListItem>

                            <ListItem v-if="keyValue.comment" class="list-item-comment">
                                <span>
                                    <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                                    <span class="font-weight-bold">Comment: </span><br>{{ keyValue.comment }}
                                </span>
                            </ListItem>
                        
                        </List>

                        <Icon v-if="isValidKeyValue" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="keyValue.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="keyValue.active.text ? 'green' : 'warning'">{{ keyValue.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Key/Value Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Key/Value Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveKeyValue()" />

                <!-- Edit Key/Value Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditKeyValue()" />

                <!-- Copy Key/Value Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneKeyValue()"/>

                <!-- Move Key/Value Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Key/Value Details  -->
                
        </div>

        <!-- 
            MODAL TO CLONE / EDIT KEY/VALUE
        -->
        <template v-if="isOpenManageKeyValueModal">

            <manageKeyValueModal
                :index="index"
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :keyValue="keyValue"
                :keyValues="keyValues"
                @visibility="isOpenManageKeyValueModal = $event">
            </manageKeyValueModal>

        </template>

    </Card>

</template>

<script>

    import manageKeyValueModal from './../manageKeyValueModal.vue';

    var localCustomMixin = require('./../../localMixin.js').default;

    export default {
        components: { manageKeyValueModal },
        mixins: [localCustomMixin],
        props: {
            index: {
                type: Number,
                default:null
            },
            keyValue: {
                type: Object,
                default:() => {}
            },
            keyValues: {
                type: Array,
                default:() => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            }
        },
        data(){

            return {
                isOpenManageKeyValueModal: false,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.keyValue.hexColor
                }
            },
            cardClasses(){
                return [
                    'single-draggable-item', 
                    (this.isExpanded ? 'active' : ''), 'mb-2'
                ]
            },
            arrowDirection(){
                return this.isExpanded ? 'ios-arrow-down' : 'ios-arrow-forward';
            },
            isValidKeyValue(){
                return true;
            },
            isValidArrayValue(){
                return true;
            },
            defaultType(){
                //  Note: defaultTypes is registered within localMixin.js
                for (let index = 0; index < this.defaultTypes.length; index++) {
                    if( this.defaultTypes[index].value ==  this.keyValue.on_empty_value.default.selected_type){
                        return this.defaultTypes[index].name;
                    }
                }
            }
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditKeyValue(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageKeyValueModal();
            },
            handleCloneKeyValue() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageKeyValueModal();
            },
            handleConfirmRemoveKeyValue(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the Key Value removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Key/Value',
                    onOk: () => { this.handleRemoveKeyValue() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.keyValue.name),
                                '". After this Key Value is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveKeyValue() {

                //  Remove Key Value from list
                this.keyValues.splice(this.index, 1);

                //  Key/Value removed success message
                this.$Message.success({
                    content: 'Array Key/Value removed!',
                    duration: 6
                });
            },
            handleOpenManageKeyValueModal(){
                this.isOpenManageKeyValueModal = true;
            }
        }
    }

</script>
