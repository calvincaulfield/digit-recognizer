cmake_minimum_required (VERSION 2.6)

set(CMAKE_CXX_STANDARD 11)

if(NOT CMAKE_BUILD_TYPE)
  set(CMAKE_BUILD_TYPE Release)
endif()

message(STATUS "Build type: ${CMAKE_BUILD_TYPE}")

set(CMAKE_CXX_FLAGS "")
set(CMAKE_CXX_FLAGS_DEBUG "-g -Wall -Wextra")
set(CMAKE_CXX_FLAGS_RELEASE "-O3")

project (DigitRecognizer)

add_executable (DigitRecognizer main)

add_library (NeuralNetwork neural_network)
add_library (HandwritternDigitAnalyzer handwritten_digit_analyzer)


target_link_libraries (DigitRecognizer HandwritternDigitAnalyzer NeuralNetwork)

add_definitions(-Wfatal-errors)