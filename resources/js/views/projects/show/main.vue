<template>
    <Layout class="border-top" :style="{ minHeight:'100em' }">
        <Header :style="{width: '100%'}" class="bg-white border-top border-bottom p-0">
            <Row :gutter="12">
                <Col :span="16" :offset="2">
                    <Breadcrumb>
                        <BreadcrumbItem to="/projects">Projects</BreadcrumbItem>
                        <BreadcrumbItem to="/projects">Project 1</BreadcrumbItem>
                        <BreadcrumbItem to="/projects">Builder</BreadcrumbItem>
                    </Breadcrumb>
                </Col>
                <Col :span="4" class="clearfix">
                    <Button type="success" size="large" class="float-right mt-2">Save Changes</Button>
                </Col>
            </Row>
        </Header>
        <Layout>

            <!-- If we are loading -->
            <template v-if="isLoading">

                <!-- Show Loader -->
                <Loader class="mt-5"></Loader>

            </template>

            <!-- If we are not loading and have the project -->
            <template v-else-if="project">

                <!-- Side Menu -->
                <Sider hide-trigger>

                    activeLink: {{ activeLink }}

                    <!-- Show Menu Links -->
                    <Menu :active-name="activeLink" theme="light" width="auto">
                        <MenuItem v-for="(menuLink, index) in menuLinks" :key="index"
                            :name="menuLink.name" class="" @click.native="navigateTo(menuLink.name)">
                            <Icon :type="menuLink.icon" :size="20" />
                            {{ menuLink.name }}
                        </MenuItem>
                    </Menu>

                </Sider>

                <!-- Content -->
                <Content>

                    <!-- Show Builder -->
                    <Builder v-if="activeLink == 'builder'" :project="project"></Builder>

                    <!-- Show Billing -->
                    <Billing v-else-if="activeLink == 'billing'" :project="project"></Billing>

                </Content>

            </template>
                    
            <!-- If we are not loading and don't have the project -->
            <template v-else-if="!project">

                <Alert type="warning" class="m-5" show-icon>
                    Project Not Found
                    <template slot="desc">
                    We could not get your project, try refreshing your browser. It's also possible that this project has been deleted.
                    </template>
                </Alert>

            </template>

        </Layout>
    </Layout>

</template>

<script>

    import Builder from './builder/main.vue';
    import Billing from './billing/main.vue';
    import Loader from './../../../components/_common/loaders/default.vue';

    export default {
        components: { Builder, Billing, Loader },
        props: {
            projectUrl: {
                type: String,
                default: null
            }
        },
        data(){
            return {
                project: null,
                activeLink: null,
                isLoading: false,
                menuLinks: [
                    {
                        name: 'overview',
                        icon: 'ios-analytics-outline'
                    },
                    {
                        name: 'builder',
                        icon: 'ios-git-branch'
                    },
                    {
                        name: 'billing',
                        icon: 'ios-cash-outline'
                    },
                    {
                        name: 'analytics',
                        icon: 'ios-stats-outline'
                    },
                    {
                        name: 'subscriptions',
                        icon: 'ios-chatboxes-outline'
                    }
                ]
            }
        },
        mounted () {
            //  Get the current route name e.g "overview" or "builder"
            this.activeLink = this.$route.name
        },
        watch: {
            //  Watch for changes of the route
            $route (newVal, oldVal) {

                //  Get the current route name e.g "overview" or "builder"
                this.activeLink = newVal.name;

            },
            //  Watch for changes of the project url
            projectUrl (newVal, oldVal) {

                //  Refetch the project
                this.fetchProject();

            }
        },
        methods: {
            navigateTo(linkName){
                
                if( this.url ){

                    //  this.$route.menu = linkName;
                    this.$router.push({ name: 'show-project', params: { url: encodeURIComponent(this.localUrl) }, query: { menu: linkName } });

                }

            },
            fetchProject() {

                //  If we have the project url
                if( this.projectUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.projectUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the project
                            self.project = data || null;

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
            }
        },
        created(){

            //  Fetch the project
            this.fetchProject();
            
        }
    }
</script>
