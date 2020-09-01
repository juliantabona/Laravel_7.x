<template>

    <Row>

        <Col v-if="isLoading" :span="24">

            <!-- Show Loader -->
            <Loader class="mt-3"></Loader>

        </Col>
        
        <template v-else>

            <Col :span="24" class="bg-white mb-3">

                <Row>

                    <Col :span="12">
                        
                        <!-- Show version editor or simulator -->
                        <Tabs v-model="activeNavTab" name="builder-tabs" class="builder-main-tabs" style="overflow: visible;" :animated="false">

                            <!-- Screen Settings Navigation Tabs -->
                            <TabPane v-for="(currentTabName, key) in navTabs" :key="key" 
                                    :label="currentTabName.name" :name="currentTabName.value" :icon="currentTabName.icon">
                            </TabPane>

                        </Tabs>

                    </Col>

                    <Col :span="10">

                        <ButtonGroup class="float-right mt-2 mr-3">
                            <Button>
                                <Icon type="ios-undo-outline" :size="24"></Icon>
                            </Button>
                            <Button class="p-0" :style="{ position: 'relative', zIndex: 5 }">
                    
                                <Poptip trigger="click" placement="bottom-end" word-wrap width="500">
                                    
                                    <Icon type="ios-time-outline" class="px-3" :size="24"></Icon>

                                    <template slot="content">
                                        
                                        <span :style="{ marginBottom: '-22px' }" class="bg-grey-light d-block font-weight-bold p-2">History</span>

                                        <Timeline :style="{ maxHeight: '200px', overflowY: 'auto' }" class="pl-2 pr-5">
                                                
                                            <TimelineItem v-for="(savedActivity, index) in savedActivities" :key="index" :style="{ marginBottom: '-20px !important' }">
                                                <p class="time text-dark" :style="{ marginBottom: '-15px' }">{{ savedActivity.time }}</p>
                                                <p v-if="savedActivity.description" class="content text-secondary">{{ savedActivity.description }}</p>
                                            </TimelineItem>

                                        </Timeline>

                                    </template>

                                </Poptip>

                            </Button>
                            <Button>
                                <Icon type="ios-redo-outline" :size="24"></Icon>
                            </Button>
                        </ButtonGroup> 

                    </Col>

                </Row>

            </Col>

            <Col :span="24">

                <editor v-show="activeNavTab == 1" :project="project" :version="version" class="p-2"></editor>

                <simulator v-show="activeNavTab == 2" :project="project" :version="version" class="p-2"></simulator>

            </Col>

        </template>

    </Row>

</template>

<script>

    import editor from './editor/main.vue';
    import simulator from './simulator/main.vue';
    import Loader from './../../../../../../components/_common/loaders/default.vue';

    export default {
        components: { editor, simulator, Loader },
        props: {
            project: {
                type: Object,
                default: null
            },
            requestToSaveChanges: {
                type: Number,
                default: 0
            }
        },
        data(){
            return {
                version: null,
                versionBeforeChanges: null,
                isLoading: false,
                isSaving: false,
                activeNavTab: '1',
                savedActivities: [
                    {
                        user: 'Julian Tabona',
                        time: '22 Aug 2020 @ 08:45',
                        description: 'Created "Add To Cart" screen and functionality'
                    },
                    {
                        user: 'Julian Tabona',
                        time: '22 Aug 2020 @ 12:03',
                        description: 'Added ability to use coupons on after adding products to cart'
                    },
                    {
                        user: 'Julian Tabona',
                        time: '23 Aug 2020 @ 14:07',
                        description: 'Added ability to create a User Account before any user can start shopping'
                    },
                    {
                        user: 'Julian Tabona',
                        time: '24 Aug 2020 @ 16:43',
                        description: 'Provided option for "Checkout - Pay Now"'
                    },
                    {
                        user: 'Julian Tabona',
                        time: '24 Aug 2020 @ 14:18',
                        description: 'Provided option for "Checkout - Pay On Delivery"'
                    },
                ],
                navTabs: [
                    {
                        icon: 'ios-git-branch',
                        name: 'Editor',
                        value: '1',
                    },
                    {
                        icon: 'ios-phone-portrait',
                        name: 'Simulator',
                        value: '2'
                    }
                ]
            }
        },
        watch: {
            /*  Keep track of changes on the version  */
            version: {

                handler: function (val, oldVal) {

                    this.notifyUnsavedChangesStatus();

                },
                deep: true

            },
            /** Watch to see if we want to save changes.
             *  If we do handle the request.
             */
            requestToSaveChanges(newVal, oldVal){

                this.saveVersion();
            }
        },
        computed: {
            versionUrl(){
                return decodeURIComponent(this.$route.params.version_url);
            }
        },
        methods: {
            copyVersionBeforeUpdate(){
                
                //  Clone the version
                this.versionBeforeChanges = _.cloneDeep( this.version );

            },
            versionHasBeenUpdated(){

                //  Check if the version has been modified
                return !_.isEqual(this.version, this.versionBeforeChanges);

            },
            notifyUnsavedChangesStatus(){

                //  Notify the parent if we have changes to save
                this.$emit('unsavedChanges', this.versionHasBeenUpdated());

            },
            fetchVersion() {

                //  If we have the version url
                if( this.versionUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.versionUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the version
                            self.version = data || null;
                
                            //  Copy the version before any chages are made
                            self.copyVersionBeforeUpdate();

                            //  Stop loader
                            self.isLoading = false;

                            self.$emit('loadedVersion', self.version)

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            },
            saveVersion() {

                //  If we have the version url
                if( this.versionUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isSaving = true;

                    this.$emit('isSaving', self.isSaving);

                    var versionPayload = this.version;

                    //  Use the api call() function, refer to api.js
                    api.call('put', this.versionUrl, versionPayload)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);
                            
                            self.copyVersionBeforeUpdate();

                            self.notifyUnsavedChangesStatus();

                            self.$Message.success({
                                content: 'Project saved!',
                                duration: 6
                            });

                            //  Stop loader
                            self.isSaving = false;

                            self.$emit('isSaving', self.isSaving);

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isSaving = false;

                            self.$emit('isSaving', self.isSaving);

                        });
                }
            }
        },
        created(){

            //  Fetch the project
            this.fetchVersion();
            
        }
    }
</script>
