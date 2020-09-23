<template>

    <div>

        <Divider orientation="left">
            <span class="text-primary">User Accounts</span>
        </Divider>
        
        <div class="clearfix">

            <!-- Add User Account Button -->
            <basicButton type="primary" size="default" icon="ios-add" :showIcon="true" iconDirection="left"
                         class="float-right mb-2" :ripple="false" :disabled="isLoading"
                         @click.native="handleCreateUserAccounts()">
                <span>Add User Account</span>
            </basicButton>

        </div>
        
        <Card class="pt-2">

            <Table :columns="columns" :data="userAccounts" :loading="isLoading" :style="{ overflow: 'initial' }"></Table>

            <div class="clearfix">

                <!-- Refresh Button -->
                <Button type="default" size="small" class="float-right mt-2" @click.native="fetchUserAccounts()">
                    <span class="d-flex">
                        <Icon type="ios-refresh" :size="20" class="mr-1"/>
                        <span>Refresh</span>
                    </span>
                </Button>

            </div>

        </Card>

        <!-- 
            MODAL TO ADD / CLONE / EDIT INSTANT CART
        -->
        <template v-if="isOpenManageUserAccountsDrawer">

            <manageUserAccountsDrawer
                :index="index"
                :project="project"
                :version="version"
                :userAccount="userAccount"
                :userAccounts="userAccounts"
                @isSaving="isLoading = $event"
                @isCreating="isLoading = $event"
                @savedUserAccount="handleSavedUserAccounts"
                @createdUserAccount="handleCreatedUserAccount($event)"
                @visibility="isOpenManageUserAccountsDrawer = $event">
            </manageUserAccountsDrawer>
    
        </template>

    </div>
    
</template>

<script>

    import manageUserAccountsDrawer from './manageUserAccountsDrawer.vue';

    /*  Loaders  */
    import Loader from'./../../../../../../../../../components/_common/loaders/default.vue';  
    import basicButton from'./../../../../../../../../../components/_common/buttons/basicButton.vue';  

    export default {
        components: { Loader, basicButton, manageUserAccountsDrawer },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            ussdSimulatorLoading: {
                type: Boolean,
                default: false
            }
        },
        data(){
            return {
                index: null,
                userAccount: null,
                userAccounts: [],
                isLoading: false,
                columns: [
                    {
                        title: 'First Name',
                        key: 'first_name'
                    },
                    {
                        title: 'Last Name',
                        key: 'last_name'
                    },
                    {
                        title: 'Mobile',
                        key: 'mobile_number'
                    },
                    {
                        title: '',
                        render: (h, params) => {
                            return [
                                h('Button', {
                                    props: {
                                        type: 'default',
                                        size: 'small',
                                        icon: 'ios-add'
                                    },
                                    class:['mr-1'],
                                    on: {
                                        click: () => {
                                            this.handleEditUserAccounts(params.row, params.index)
                                        }
                                    }
                                }, [
                                    h('Icon', {
                                        props: {
                                            type: 'ios-chatboxes-outline'
                                        },
                                        class:['mr-1']
                                    }),
                                    h('span', 'Messages')
                                ]),
                                h('Button', {
                                    props: {
                                        type: 'primary',
                                        size: 'small'
                                    },
                                    on: {
                                        click: () => {
                                            this.handleEditUserAccounts(params.row, params.index)
                                        }
                                    }
                                }, 'Edit')
                            ]
                        }
                    }
                ],
                isOpenManageUserAccountsDrawer: false,
            }
        }, 
        computed: {
            
            userAccountsUrl(){
                return this.project['_links']['sce:test-user-accounts'].href;
            }
            
        },
        methods: {
            handleCreatedUserAccount(userAccount){
                //  Add the new created user account to the top of the list
                this.userAccounts.unshift(userAccount);
            },
            handleEditUserAccounts(userAccount, index){
                this.index = index;
                this.userAccount = userAccount;
                this.isOpenManageUserAccountsDrawer = true;
            },
            handleSavedUserAccounts(userAccount, index){
                console.log('userAccount');
                console.log(userAccount);
                console.log('index');
                console.log(index);
                //  Update the user accounts
                this.$set(this.userAccounts, index, userAccount);

            },
            fetchUserAccounts() {

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isLoading = true;

                //  Use the api call() function, refer to api.js
                api.call('get', this.userAccountsUrl)
                    .then(({data}) => {
                        
                        //  Console log the data returned
                        console.log(data);

                        //  Get the user accounts
                        self.userAccounts = data['_embedded']['user_accounts'] || [];

                        //  Stop loader
                        self.isLoading = false;

                    })         
                    .catch(response => { 

                        //  Log the responce
                        console.error(response);

                        //  Stop loader
                        self.isLoading = false;

                    });
                
            }
        },
        created(){

            //  Fetch the user accounts
            this.fetchUserAccounts();

        }
    };
  
</script>