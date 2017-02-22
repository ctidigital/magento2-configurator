Feature: Component to manage Websites, Stores and Store Views
  In order to manage Magento websites and configuration
  As an developer
  I want to keep track of configuration changes based on the environment I am developing on

  Scenario: Websites, Stores and Store Views
    Given I have a yaml file which describes some websites and stores
    When I run the configurator's cli tool with websites component for local environment
    Then Magento database should have the desired websites and stores

  Scenario Outline: Updating store configuration
    Given I have yaml files which describes store configuration for all environments
    And I have yaml files which describes store configuration for <Environment> environment
    When I run the configurator's cli tool with config component for <Environment> environment
    Then Magento database should have the desired configuration applied for <Environment> environment

    Examples:
      | Environment |
      | local       |
      | production  |
