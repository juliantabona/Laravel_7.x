<template>

    <Row id="dashboard-header" class="bg-white py-3">

        <Col :span="20" :offset="2" class="clearfix">

            <slot>
                <h3 class="float-left">Dashboard</h3>
            </slot>

            <Dropdown trigger="click" @on-click="handleSelection" class="float-right">
                <span>
                    <span class="font-weight-bold mr-1">{{ user.name }}</span>
                    <Icon class="font-weight-bold mr-1" type="ios-arrow-down"></Icon>
                    <Avatar icon="ios-person" />
                </span>
                <DropdownMenu slot="list">
                    <DropdownItem name="projects">My Projects</DropdownItem>
                    <DropdownItem name="logout">Sign Out</DropdownItem>
                </DropdownMenu>
            </Dropdown>

        </Col>

    </Row>

</template>

<script>

    export default {
        data(){
            return {
                //  Access the user from auth.js
                isLoading: false,
                user: auth.getUser()
            }
        },
        methods: {
            handleSelection(selected){

                //  If we want to view projects
                if(selected == 'projects'){

                    //  Redirect to the projects page
                    this.navigate();

                //  If we want to logout
                }else if(selected == 'logout'){

                    //  Attempt to logout
                    this.attemptLogout();

                }
            },
            attemptLogout(){

                //  Hold constant reference to the current Vue instance
                const self = this;

                //  Start loader
                self.isLoading = true;

                console.log('Attempting to logout');

                /** Attempt to logout using the auth logout method found in the auth.js file.
                 *  Note that on a successful logout, this method will automatically handle
                 *  the process of redirecting the user to the login page. 
                 */
                auth.logout()
                    .then((data) => {

                        //  Stop loader
                        self.isLoading = false;
                        
                    }).catch((error) => {
                
                        console.log(error);

                });
            }
        }
    };

</script>