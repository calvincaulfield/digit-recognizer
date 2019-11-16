#include "handwritten_digit_analyzer.h"
#include <iostream>
#include <fstream>
#include <sstream>
#include <climits>

namespace digit_recognizer
{
using std::ifstream;

handwritten_digits_analyzer::handwritten_digits_analyzer(
	vector<size_t> hiddenLayer, double learningRate) : neuralNetwork_(28 * 28, 10, hiddenLayer, learningRate)
{
}

vector<std::pair<vector<double>, vector<double>>>
handwritten_digits_analyzer::readBinaryFile(std::string imageFileName, std::string labelFileName) const
{
	vector<std::pair<vector<double>, vector<double>>> data;
	ifstream imageFile(imageFileName, std::ios::binary);
	ifstream labelFile(labelFileName, std::ios::binary);

	std::cout << "Checking image data file...\n";
	std::cout << "magic num: " << readUnsignedInt(imageFile) << "\n";

	size_t numItems = readUnsignedInt(imageFile);
	size_t numRows = readUnsignedInt(imageFile);
	size_t numColumns = readUnsignedInt(imageFile);

	std::cout << "# of items: " << numItems << "\n";
	std::cout << "# of rows: " << numRows << "\n";
	std::cout << "# of columns: " << numColumns << "\n\n";

	std::cout << "Checking label data file...\n";
	std::cout << "magic num: " << readUnsignedInt(labelFile) << "\n";
	std::cout << "# of items: " << readUnsignedInt(labelFile) << "\n";

	std::cout << "Starting to load data.\n";
	for (size_t item = 0; item < numItems; item++)
	{
		vector<double> input;
		vector<double> output(10, 0.0);
		unsigned char label = readUnsignedByte(labelFile);
		output[label] = 1.0;

		for (size_t column = 0; column < numColumns; column++)
		{
			for (size_t row = 0; row < numRows; row++)
			{
				unsigned char byte = readUnsignedByte(imageFile);
				double pixel = static_cast<double>(byte) / UCHAR_MAX;
				input.push_back(pixel);
			}
		}
		data.push_back(std::make_pair(input, output));
	}
	std::cout << "Finished loading data.\n\n";
	return data;
}

vector<double>
handwritten_digits_analyzer::trainWithBinaryFile(size_t numIter,
												 std::string trainImageFile, std::string trainLabelFile,
												 std::string testImageFile, std::string testLabelFile)
{
	auto trainData = readBinaryFile(trainImageFile, trainLabelFile);
	auto testData = readBinaryFile(testImageFile, testLabelFile);
	std::cout << "Start training.\n";
	return neuralNetwork_.train(numIter, trainData, testData);
	std::cout << "End training.\n";
}

vector<double>
handwritten_digits_analyzer::trainWithTextFile(size_t numIter, std::string trainFileName, std::string testFileName)
{
	std::cout << "Start training.\n";
	size_t trainDataSize = 100000;
	size_t testDataSize = 10000;

	auto trainData = readFile(trainFileName, trainDataSize);
	auto testData = readFile(testFileName, testDataSize);

	return neuralNetwork_.train(numIter, trainData, testData);
	std::cout << "End training.\n";
}

vector<std::pair<vector<double>, vector<double>>>
handwritten_digits_analyzer::readFile(std::string fileName, size_t count) const
{
	vector<std::pair<vector<double>, vector<double>>> data;
	ifstream dataFile(fileName);
	std::string line;
	std::string token;
	char delim = ',';
	std::cout << "Starting to load data: " << fileName << "\n";
	for (size_t i = 0; getline(dataFile, line) && i < count; i++)
	{
		vector<double> input;
		vector<double> output(10, 0.0);
		vector<int> values;
		std::stringstream stringStream(line);
		while (getline(stringStream, token, delim))
		{
			values.push_back(std::stoi(token));
		}

		for (size_t i = 0; i < values.size(); i++)
		{
			int value = values[i];
			//std::cout << value << ",";
			if (i == 0)
			{ // first tokoen is output
				output[static_cast<size_t>(value)] = 1.0;
			}
			else
			{
				input.push_back(static_cast<double>(value) / UCHAR_MAX);
			}
		}
		data.push_back(std::make_pair(input, output));
	}
	std::cout << "Finished loading data.\n\n";
	return data;
}

unsigned char
handwritten_digits_analyzer::readUnsignedByte(std::ifstream &file) const
{
	char buffer[1];
	file.read(buffer, 1);
	if (!file)
	{
		std::cout << "readByte error!\n";
		exit(0);
	}
	return static_cast<unsigned char>(buffer[0]);
}

uint32_t
handwritten_digits_analyzer::readUnsignedInt(ifstream &file) const
{
	char buffer[4];
	file.read(buffer, 4);
	if (!file)
	{
		std::cout << "readOneUnsigned error!\n";
		exit(0);
	}
	return convertFromBigEndian(buffer);
}

uint32_t
handwritten_digits_analyzer::convertFromBigEndian(char buffer[4]) const
{
	return static_cast<unsigned char>(buffer[0]) << 24 |
		   static_cast<unsigned char>(buffer[1]) << 16 |
		   static_cast<unsigned char>(buffer[2]) << 8 |
		   static_cast<unsigned char>(buffer[3]);
}

void handwritten_digits_analyzer::showBytes(ifstream &file, size_t size) const
{
	for (int i = 0; i < size; i++)
	{
		std::cout << i << " " << static_cast<int>(readUnsignedByte(file)) << "\n";
	}
}

}